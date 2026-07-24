<?php
namespace App\traits;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\URL;
use Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GenericImport;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use App\Models\income;
use App\Models\income_advance;
use App\Models\income_license;
use App\Models\client;
use App\Models\license;
use App\Models\license_notification;

use Carbon\Carbon;


use Illuminate\Support\Str;

trait incomes_trait
{
    use
        pdf_trait
        , clients_trait
        , mail_trait
        , open_ia_trait
        , twilio_sms_trait
    ;
    //Add License
    public function Income_Createincome(
        $state,
        $client_id,
        $client_identification,
        $client_name,
        $timely_payment,
        $cutoff_date,
        $description,
        $licenses,
        $bill_name = null,
        $bill_final_value = null,
        $create_at = null
    ) {
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try {
            $dbLicenses = license::whereIn('id', collect($licenses)->pluck('license_id'))->get();
            $income = new income();
            $income->unique_id = strtoupper(strtoupper(Str::uuid()->toString()));
            $income->state = $state;
            $income->client_id = $client_id;
            $income->client_identification = $client_identification;
            $income->client_name = $client_name;
            $income->timely_payment = $timely_payment;
            $income->cutoff_date = $cutoff_date;
            $income->description = $description;
            $income->total = collect($licenses)->sum('total');
            $income->bill_name = $bill_name;
            $income->bill_final_value = $bill_final_value;
            if ($create_at != null) {
                $income->created_at = $create_at;
            }
            $income->save();
            $licensesList = [];
            $timely_payment = Carbon::parse($timely_payment);
            foreach ($licenses as $item) {
                $dbLicense = $dbLicenses->where('id', $item['license_id'])->first();
                if ($dbLicense) {
                    $income_license = new income_license();
                    $income_license->income_id = $income->id;
                    $income_license->license_id = $item['license_id'];
                    $income_license->license_name = $item['license_name'];
                    $income_license->timely_payment = Carbon::parse($timely_payment);
                    $income_license->service_id = $item['service_id'];
                    $income_license->service_name = $item['service_name'];
                    $income_license->recurrence_months = $item['recurrence_months'];
                    $income_license->value = $item['value'];
                    $income_license->comission = $item['comission'];
                    $income_license->employee_id = $item['employee_id'];
                    $income_license->employee_name = $item['employee_name'];
                    $income_license->tax_id = $item['tax_id'];
                    $income_license->tax_name = $item['tax_name'];
                    $income_license->tax_value = ($item['tax_value'] == null || $item['tax_value'] == '') ? 0 : $item['tax_value'];
                    $income_license->description = $item['description'];
                    $income_license->total = $item['total'];
                    $income_license->hours = $item['hours'];
                    $income_license->save();
                    $licensesList[] = $income_license;
                }
            }
            $url = url('/') . '/client/payments/pay/' . $income->unique_id;
            $income['licenses'] = $licensesList;
            $clientResponse = $this->Client_GetClientById($client_id);
            $pdfResponse = null;
            if ($clientResponse['status'] == 1) {
                $purchase_state = [2, 3, 4];
                if (in_array($state, $purchase_state)) {
                    QrCode::format('png')->size(500)->generate($url, Storage::disk('incomes_qr')->path($income->unique_id . '.png'));
                    $pdfResponse = $this->Income_CreateOrderPurchasePdf($income, $clientResponse['data']);
                } else {
                    $pdfResponse = $this->Income_CreateOrderQuotationPdf($income, $clientResponse['data']);
                }
            }
            $Response['status'] = 1;
            $Response['message'] = 'Income created';
            $Response['data'] = [
                'income' => $income,
                'pdfResponse' => $pdfResponse
            ];
        } catch (\Exception $e) {
            info('Income_Createincome error: ' . $e->getMessage());
            $Response['message'] = 'Income_Createincome: ' . $e->getMessage();
        }
        return $Response;
    }

    public function Income_CreateMassive($request)
    {
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        $request->validate([
            'import_file_input' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $import = new GenericImport();
            $rows = Excel::toCollection($import, $request->file('import_file_input'))[0]; // Primera hoja
            $rows = $rows->skip(1); // Omite encabezado

            $grouped = $rows->groupBy(function ($row): mixed {
                return $row[0];
            });
            $results = [];

            foreach ($grouped as $client_unique_id => $items) {
                $firstRow = $items->first();
                $timely_payment = Date::excelToDateTimeObject($firstRow[1]); // Columna B
                $timely_payment = Carbon::instance($timely_payment);
                $cutoff_date = Date::excelToDateTimeObject($firstRow[2]);// Columna C 
                $cutoff_date = Carbon::instance($cutoff_date);
                $client = client::where('unique_id', $client_unique_id)->first();
                if (!$client) {
                    \Log::warning("Cliente no encontrado: ID $client_unique_id");
                    continue;
                }

                $licenses = [];
                $license_ids = $items->pluck(3)->toArray(); // Columna D: licencia_id
                $dbLicenses = license::with(['service','service.tax'])->whereIn('unique_id', $license_ids)->get();

                foreach ($items as $row) {
                    $license_id = $row[3];
                    $value = (float) $row[4]; // Columna E
                    $hours = (int) $row[5];   // Columna F
                    $commission = (float) $row[6]; // Columna G
                    $desc = trim((string) $row[7]); // Columna H

                    $license = $dbLicenses->where('unique_id', $license_id)->first();
                    if (!$license) {
                        \Log::warning("Licencia no encontrada: ID $license_id");
                        continue;
                    }
                    $tax_id = null;
                    $tax_name = null;
                    $tax_value = null;
                    if ($license->service && $license->service->tax) {
                        $tax_name = $license->service->tax->name;
                        $tax_value = $license->service->tax->value;
                    }

                    $licenses[] = [
                        'license_id' => $license->id,
                        'license_name' => $license->name,
                        'service_id' => $license->service_id,
                        'service_name' => optional($license->service)->name,
                        'recurrence_months' => $license->type==2?null:$license->recurrence_months,
                        'value' => $value,
                        'comission' => $commission,
                        'employee_id' => $license->employee_id,
                        'employee_name' => optional($license->employee)->name,
                        'tax_id' => $tax_id,
                        'tax_name' => $tax_name,
                        'tax_value' => $tax_value, // Valor por defecto del IVA
                        'description' => $desc ?: '',
                        'total' => $value + ($value * ($tax_value??0 / 100)),
                        'hours' => $hours,
                    ];
                }

                $response = $this->Income_Createincome(
                    0, // Estado de cotización
                    $client->id,
                    $client->identification,
                    $client->name,
                    $timely_payment,
                    $cutoff_date,
                    '',
                    $licenses
                );

                $results[] = $response;
            }

            $Response['status'] = 1;
            $Response['message'] = 'Incomes creados exitosamente';
            $Response['data'] = $results;

        } catch (\Exception $e) {
            \Log::error('Error al crear ingresos masivos: ' . $e->getMessage());
            $Response['message'] = 'Error al crear ingresos masivos: ' . $e->getMessage();
        }

        return $Response;
    }

    //Get incomes page
    public function Income_GetPage(
        $pagination
        ,
        $search
        ,
        $state = null
        ,
        $with_trashed = false
    ) {
        try {
            $incomes = income::orderBy('created_at', 'desc');
            if ($with_trashed) {
                $incomes = $incomes->withTrashed();
            }
            if ($state != null && $state != '' && $state != '-1')
                $incomes = $incomes->where('state', $state);
            if ($search != null && $search != '') {
                $incomes = $incomes->where(function ($query) use ($search) {
                    $query->where('unique_id', 'like', '%' . $search . '%')
                        ->orWhere('client_identification', 'like', '%' . $search . '%')
                        ->orWhere('client_name', 'like', '%' . $search . '%')
                        ->orWhere('total', 'like', '%' . $search . '%')
                    ;
                });
            }
            $pagination['total'] = $incomes->count();
            $pagination['totalPages'] = ceil($pagination['total'] / $pagination['per_page']);
            $incomes = $incomes->skip((($pagination['page'] - 1) * $pagination['per_page']))->take($pagination['per_page'])->get();
            //assoc data
            foreach ($incomes as $item) {
                $item->bill = null;
            }
            return [
                'status' => 1,
                'message' => 'Ingresos obtenidos',
                'pagination' => $pagination,
                'data' => $incomes
            ];
        } catch (\Exception $e) {
            info('Income_GetPage error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get income page by client id
    public function Income_GetPageByClientId(
        $client_id
        ,
        $pagination
        ,
        $search
        ,
        $state = null
        ,
        $with_trashed = false
    ) {
        try {
            $incomes = income::where('client_id', $client_id)->orderBy('created_at', 'desc');
            if ($with_trashed) {
                $incomes = $incomes->withTrashed();
            }
            if ($state != null && $state != '')
                $incomes = $incomes->where('state', $state);
            if ($search != null && $search != '') {
                $incomes = $incomes->where('unique_id', 'like', '%' . $search . '%')
                    ->orWhere('client_identification', 'like', '%' . $search . '%')
                    ->orWhere('client_name', 'like', '%' . $search . '%')
                    ->orWhere('total', 'like', '%' . $search . '%')
                ;
            }
            $pagination['total'] = $incomes->count();
            $pagination['totalPages'] = ceil($pagination['total'] / $pagination['per_page']);
            $incomes = $incomes->skip((($pagination['page'] - 1) * $pagination['per_page']))->take($pagination['per_page'])->get();
            //assoc data
            foreach ($incomes as $item) {
                $item->bill = null;
            }
            return [
                'status' => 1,
                'message' => 'Ingresos obtenidos',
                'pagination' => $pagination,
                'data' => $incomes
            ];
        } catch (\Exception $e) {
            info('Income_GetPageByClientId error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get income licenses
    public function Income_GetIncomeLicenses(
        $income_id
    ) {
        try {
            //Filter by current client
            if (Session::has('client_user')) {
                $client_id = Session::get('client_user')['active_client']['id'];
                $income = income::where('id', $income_id)->where('client_id', $client_id)->first();
                if ($income == null) {
                    return [
                        'status' => 0,
                        'message' => 'Income not found'
                    ];
                }
            }
            $income_licenses = income_license::where('income_id', $income_id)->get();
            return [
                'status' => 1,
                'message' => 'Income licenses obtained',
                'data' => $income_licenses
            ];
        } catch (\Exception $e) {
            info('Income_GetIncomeLicenses error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Update Income
    public function Income_UpdateIncome(
        $id,
        $state,
        $client_id,
        $client_identification,
        $client_name,
        $timely_payment,
        $cutoff_date,
        $description,
        $bill_name,
        $bill_final_value,
        $licenses
    ) {
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try {
            $income = income::find($id);
            if ($income == null) {
                $Response['message'] = 'Income not found';
                return $Response;
            }

            $last_state = $income->state;
            if ($last_state == 3 || $last_state == 4) {
                $Response['message'] = 'No se puede cambiar el estado de un ingreso anulado o eliminado';
                $Response['status'] = 0;
                return $Response;
            }

            $income->state = $state;
            $income->client_id = $client_id;
            $income->client_identification = $client_identification;
            $income->client_name = $client_name;
            $income->timely_payment = $timely_payment;
            $income->cutoff_date = $cutoff_date;
            $income->description = $description;
            $income->bill_name = $bill_name;
            $income->bill_final_value = $bill_final_value;
            $income->total = collect($licenses)->sum('total');
            $income->save();
            income_license::where('income_id', $id)->delete();
            $licensesList = [];
            foreach ($licenses as $item) {
                $income_license = new income_license();
                $income_license->income_id = $income->id;
                $income_license->license_id = $item['license_id'];
                $income_license->license_name = $item['license_name'];
                $income_license->service_id = $item['service_id'];
                $income_license->service_name = $item['service_name'];
                $income_license->recurrence_months = $item['recurrence_months'];
                $income_license->value = $item['value'];
                $income_license->comission = $item['comission'];
                $income_license->employee_id = $item['employee_id'];
                $income_license->employee_name = $item['employee_name'];
                $income_license->tax_id = $item['tax_id'];
                $income_license->tax_name = $item['tax_name'];
                $income_license->tax_value = ($item['tax_value'] == null || $item['tax_value'] == '') ? 0 : $item['tax_value'];
                $income_license->description = $item['description'];
                $income_license->total = $item['total'];
                $income_license->hours = $item['hours'];
                $income_license->save();
                $licensesList[] = $income_license;
            }
            if ($last_state != 3 && $last_state != 4) {
                $url = url('/') . '/client/payments/pay/' . $income->unique_id;
                $income['licenses'] = $licensesList;
                //re-generate pdf
                $client = $this->Client_GetClientById($client_id);
                if ($client['status'] == 1) {
                    if ($income->state == 2 || $income->state == 3 || $income->state == 4) {
                        QrCode::format('png')->size(500)->generate($url, Storage::disk('incomes_qr')->path($income->unique_id . '.png'));
                        $pdfResponse = $this->Income_CreateOrderPurchasePdf($income, $client['data']);
                    } else {
                        $pdfResponse = $this->Income_CreateOrderQuotationPdf($income, $client['data']);
                    }
                }
            }
            $Response['status'] = 1;
            $Response['message'] = 'Income updated';
            $Response['data'] = $income;
        } catch (\Exception $e) {
            info('Income_UpdateIncome error: ' . $e->getMessage());
            $Response['message'] = 'Income_UpdateIncome: ' . $e->getMessage();
        }
        return $Response;
    }
    //Create order purchase pdf
    public function Income_CreateOrderPurchasePdf($income, $client)
    {
        $Response = array(
            'status' => 0,
            'message' => '',
            'data' => null
        );
        /*sort licenses by service name and license name*/
        $income->licenses = collect($income->licenses)->sortBy(function ($item) {
            return $item['service_name'] . $item['license_name'];
        })->values();
        $Data = [
            'income' => $income,
            'client' => $client,
            'public_path' => public_path() . '/',
            'storage_path' => storage_path('app/public') . '/'
        ];
        try {
            $pdf = $this->PDF_GenerarPDF('pdf.purchase_order', $Data);
            Storage::disk('incomes_pdfs')->put($income->unique_id . '.pdf', $pdf->output());
            $Response['status'] = 1;
            $Response['message'] = 'Order purchase pdf created';
        } catch (\Exception $e) {
            info('Income_CreateOrderPurchasePdf error: ' . $e->getMessage());
            $Response['message'] = 'Income_CreateOrderPurchasePdf: ' . $e->getMessage();
        }
        $Response['data'] = $Data;
        return $Response;
    }
    //Create order quotation pdf
    public function Income_CreateOrderQuotationPdf($income, $client)
    {
        $Response = array(
            'status' => 0,
            'message' => '',
            'data' => null
        );
        /*sort licenses by service name and license name*/
        $income->licenses = collect($income->licenses)->sortBy(function ($item) {
            return $item['service_name'] . $item['license_name'];
        })->values();
        $Data = [
            'income' => $income->toArray(),
            'client' => $client->toArray(),
            'public_path' => public_path() . '/',
            'storage_path' => storage_path('app/public') . '/'
        ];
        try {
            $pdf = $this->PDF_GenerarPDF('pdf.quotation_order', $Data);
            Storage::disk('incomes_pdfs')->put($income->unique_id . '.pdf', $pdf->output());
            $Response['status'] = 1;
            $Response['message'] = 'Order quotation pdf created';
        } catch (\Exception $e) {
            info('Income_CreateOrderQuotationPdf error: ' . $e->getMessage());
            $Response['message'] = 'Income_CreateOrderQuotationPdf: ' . $e->getMessage();
        }
        $Response['data'] = $Data;
        return $Response;
    }
    //Send income
    public function Income_SendIncome(
        $income_id
        ,
        $receivers
    ) {
        $Response = array(
            'status' => 0,
            'message' => '',
            'data' => null
        );
        try {
            $income = income::find($income_id);
            if ($income == null) {
                $Response['message'] = 'Income not found';
                return $Response;
            }
            $client = $this->Client_GetClientById($income->client_id);
            if ($client['status'] == 0) {
                $Response['message'] = 'Client not found';
                return $Response;
            }
            $licenses = $this->Income_GetIncomeLicenses($income_id);
            if ($licenses['status'] == 0) {
                $Response['message'] = 'Licenses not found';
                return $Response;
            } else if ($licenses['data']->count() == 0) {
                $Response['message'] = 'Licenses not found';
                return $Response;
            }
            $income['licenses'] = $licenses['data'];
            $client = $client['data'];
            if ($client->active == 1) {
                $income_url = env('APP_URL') . 'client/payments/pay/' . $income->unique_id;
                $order_id = substr($income->unique_id, -10);
                //IA MESSAGE
                $Response = $this->OpenIA_MakeQuestion(
                    'Eres una empresa de software, debes escribir un mensaje publicitario hacia uno de tus clientes. Debes incentivar al cliente a consumir los servicios de ' . $income['licenses'][0]['service_name'] . ' Y explicarle por qué este servicio agrega valor a sus negocios. No más de 150 caracteres. Usa un lenguaje profesional, no agregues hashtags, sin llamados a la acción.'
                );
                if ($Response['status'] == 1) {
                    $ia_message = $Response['data'][0];
                } else {
                    $ia_message = 'Optimiza tu productividad y eficiencia con nuestra solución de software: automatización inteligente para simplificar tus procesos comerciales.';
                }
                /////
                $Mails = [];
                foreach ($receivers as $item) {
                    $Mails[] = [
                        'address' => $item['email'],
                        'name' => $income->client_name
                    ];
                }
                $MailData =
                    [
                        'subject' => ($income->state == 2 ? 'Orden de compra' : 'Cotización') . ' #' . $order_id,
                    ];
                $View = ($income->state == 2 ? 'mail.purchase_order' : 'mail.quotation_order');
                $ViewData = collect(
                    [
                        'income' => $income,
                        'client' => $client,
                        'ia_message' => $ia_message,
                        'income_url' => $income_url
                    ]
                );
                //get the last 10 characters of string
                $attachments = [
                    [
                        'path' => Storage::disk('incomes_pdfs')->path($income->unique_id . '.pdf'),
                        'name' => ($income->state == 2 ? 'Orden de compra' : 'Cotización') . $order_id . '.pdf'
                    ]
                ];
                $MailResponse = $this->SendMail_attach_array($MailData, $Mails, $View, $ViewData, $attachments);

                //Send SMS
                $Message = 'Hola ' . $income->client_name . ', generamos la ' . ($income->state == 2 ? 'Orden de compra' : 'Cotización') . ' #' . $order_id . ' por un valor de COP $' . number_format($income->total, 0, ',', '.') . '. Paga antes del ' . $income->cutoff_date . ' en ' . $income->payment_link;
                foreach ($receivers as $item) {
                    try {
                        if ($item['phone'] != null && $item['phone'] != '') {
                            $Response = $this->TwilioSMS_SendMessage('+57', $item['phone'], $Message);
                        }
                    } catch (\Exception $e) {
                        $Response['message'] = 'Income_SendIncome SMS: ' . $e->getMessage();
                    }
                }
                $Response['status'] = 1;

            }
        } catch (\Exception $e) {
            info('Income_SendIncome error: ' . $e->getMessage());
            $Response['message'] = 'Income_SendIncome: ' . $e->getMessage();
        }
        return $Response;
    }
    //Get income data for unlogged user
    public function Income_GetIncomeDataForPaymentUnlogged(
        $unique_id
    ) {
        $Response = array(
            'status' => 1,
            'message' => 'Error',
            'data' => [
                'able_to_pay' => false,
                'status' => 1
            ]
        );
        try {
            //Payment sould be pending or aproved
            $enable_states = [0, 2];
            $income = income::where('unique_id', $unique_id)->first();
            if ($income != null) {
                if ($income->payment_state == 0) {
                    if (in_array($income->state, $enable_states)) {
                        $licenses = income_license::where('income_id', $income->id)->get();
                        $licences_return_data = [];
                        foreach ($licenses as $item) {
                            $licences_return_data[] = [
                                'license_name' => $item->license_name,
                                'service_name' => $item->service_name,
                                'value' => $item->value,
                                'tax_name' => $item->tax_name,
                                'tax_value' => ($item['tax_value'] == null || $item['tax_value'] == '') ? 0 : ($item['tax_value']*100),
                                'description' => $item->description,
                                'total' => $item->total_string
                            ];
                        }
                        
                        // Calcular abonos y saldo pendiente
                        $total_advances = $income->income_advances()->sum('amount');
                        $balance_pending = $income->total - $total_advances;
                        
                        $income_return_data = [
                            'client_name' => $income->client_name,
                            'timely_payment' => $income->timely_payment,
                            'timely_payment_for_human' => Carbon::parse($income->timely_payment)->diffForHumans(),
                            'cutoff_date' => $income->cutoff_date,
                            'cutoff_date_for_human' => Carbon::parse($income->cutoff_date)->diffForHumans(),
                            'total' => $income->total_string,
                            'total_advances' => number_format($total_advances, 0, ',', '.'),
                            'balance_pending' => number_format($balance_pending, 0, ',', '.'),
                            'balance_pending_raw' => $balance_pending,
                            'has_advances' => $total_advances > 0,
                            'licenses' => $licences_return_data
                        ];
                        $Response = [
                            'status' => 1,
                            'message' => 'Income obtained',
                            'data' => [
                                'income' => $income_return_data,
                                'able_to_pay' => true
                            ]
                        ];
                    } else {
                        $Response['message'] = 'Income is not available for payment';
                        $Response['data']['status'] = 4;//Income not available for payment
                    }
                } else if ($income->payment_state != 0) {

                    $Response['message'] = 'Income is currently being processed';
                    $Response['data']['status'] = 3;//Income is currently being processed
                }
            } else {
                $Response['message'] = 'Income not found';
                $Response['data']['status'] = 2;//Income not found
            }

        } catch (\Exception $e) {
            info('Income_GetIncomeDataForPaymentUnlogged error: ' . $e->getMessage());
            $Response['message'] = 'Income_GetIncomeDataForPaymentUnlogged: ' . $e->getMessage();
        }
        return $Response;
    }
    //Create siigo invoice
    /**
     * Create a Siigo electronic invoice for an income.
     *
     * @param  \App\Models\income  $income
     * @param  bool  $markAsPaid  When true the invoice is created as immediately paid
     *                            (no due_date in payments). Used by the Nini integration
     *                            since recharges are already paid before the invoice is issued.
     */
    public function Income_CreateSiigoInvoice($income, bool $markAsPaid = false)
    {
        $licenses = $this->Income_GetIncomeLicenses($income->id)['data'];
        $items = [];
        foreach ($licenses as $license) {
            if($license->value <= 0){
                continue; // Skip items with zero value
            }
            $taxes = [];
            $service_code = "DS";
            if(
                $license->service_id == 1
                || $license->service_id == 2
                || $license->service_id == 3
                || $license->service_id == 5
                || $license->service_id == 9
            ){
                $service_code = "DS";
            }else if(
                $license->service_id == 7
            ){
                $service_code = "SAP";
            }else if(
                $license->service_id == 6
            ){
                $service_code = "UXUI";
            }else if(
                $license->service_id == 4
            ){
                $service_code = "POS";
            }else if(
                $license->service_id == 8
            ){
                $service_code = "SAP";
            }
            if ($license->tax_value == 0.19){
                $taxes[] = [
                    "id" => $this->Siggo_TaxAddId, // IVA 19%
                    "name" => $this->Siggo_TaxAddName,
                    "percentage" => 19,
                    "type" => "IVA"
                ];
            } else if ($license->tax_value == 2.5) {
                $taxes[] = [
                    "id" => $this->Siggo_TaxDiscountId, // IVA 2.5%
                    "name" => $this->Siggo_TaxDiscountName,
                    "percentage" => 2.5,
                    "type" => "IVA"
                ];
            }
            $description = $license->description;
            if($description == null || $description == ''){
                $description = $license->license_name;
            }
            $items[] = [
                "code" => $service_code, // Default product code for services
                "description" => $description,
                "quantity" => 1,
                "price" => floatval($license->value),
                "discount" => 0,
                "taxes" => $taxes,
            ];
        }

        // Get payment methods from Siigo
         /*
        $paymentMethodsResponse = $this->SiigoNew_GetPaymentMethods();
        if ($paymentMethodsResponse['status'] != 1) {
            info('Error getting Siigo payment methods: ' . json_encode($paymentMethodsResponse));
            return [
                'status' => 0,
                'message' => 'Error getting payment methods from Siigo',
                'data' => null
            ];
        }

        // Find the bank transfer payment method
       
        $bankTransferMethod = collect($paymentMethodsResponse['data'])->first(function ($method) {
            return strtolower($method['name']) === 'clientes nacionales';
        });

        if (!$bankTransferMethod) {
            info('Bank transfer payment method not found in Siigo');
            return [
                'status' => 0,
                'message' => 'Bank transfer payment method not found in Siigo',
                'data' => null
            ];
        }
        */
        // Set up payment with the correct payment method ID
        // When $markAsPaid is true we use today's date as due_date since
        // the invoice is already paid. Otherwise use the cutoff_date.
        $paymentEntry = [
            "id" => 2486,
            "value" => floatval($income->total),
            "due_date" => $markAsPaid
                ? Carbon::now()->format('Y-m-d')
                : Carbon::parse($income->cutoff_date)->format('Y-m-d'),
        ];

        $payments = [$paymentEntry];

        // Create Siigo electronic invoice
        $siigoResponse = $this->SiigoNew_CreateNewElectronicBill(
            Carbon::now(),
            $income->client_identification,
            0,
            $items,
            $payments,
            "Factura generada automáticamente",
            (string) $income->unique_id
        );
        if ($siigoResponse['status'] == 1) {
            $income->siigo_invoice_id = $siigoResponse['data']['id'];
            $income->siigo_document_id = $siigoResponse['data']['document']['id'];
            $income->siigo_invoice_url = $siigoResponse['data']['public_url'];
            $income->bill_name = $income->bill_name != null ? $income->bill_name : $siigoResponse['data']['name'];
            $income->save();
            return [
                'status' => 1,
                'message' => 'Siigo invoice created successfully',
                'data' => $siigoResponse['data']
            ];
        } else {
            info('Error creating Siigo invoice: ' . json_encode($siigoResponse));
        }
        return [
            'status' => 0,
            'message' => 'Error creating Siigo invoice',
            'data' => null
        ];
    }

    //Update income payment data
    public function Income_UpdateIncomePaymentData(
        $income_id
        ,
        $payment_state
        ,
        $payment_date
        ,
        $payment_reference
        ,
        $bill_name
        ,
        $bill_final_value
        ,
        $notify_client = false
    ) {
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try {
            $income = income::find($income_id);
            if ($income == null) {
                $Response['message'] = 'Income not found';
                return $Response;
            }
            $income->state = 3;
            $income->payment_state = $payment_state;
            $income->payment_date = $payment_date;
            $income->payment_reference = $payment_reference;
            $income->bill_name = $bill_name;
            $income->bill_final_value = $bill_final_value;
            $income->save();

            if ($income->siigo_invoice_id == null && ($income->bill_name == null || $income->bill_name == '')) {
                $this->Income_CreateSiigoInvoice($income);
            }

            if ($notify_client) {
                $notifyResponse = $this->Income_SendClientPaymentThanksEmail($income);
                if ($notifyResponse['status'] == 0) {
                    info('Income_UpdateIncomePaymentData notify warning: ' . $notifyResponse['message']);
                }
            }

            $Response['status'] = 1;
            $Response['message'] = 'Income payment data updated';
            $Response['data'] = $income;
        } catch (\Exception $e) {
            info('Income_UpdateIncomePaymentData error: ' . $e->getMessage());
            $Response['message'] = 'Income_UpdateIncomePaymentData: ' . $e->getMessage();
        }
        return $Response;
    }

    public function Income_SendClientPaymentThanksEmail($income)
    {
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => null
        ];
        try {
            $licenseIds = income_license::where('income_id', $income->id)->pluck('license_id');
            if ($licenseIds->count() == 0) {
                $Response['message'] = 'Income without licenses';
                return $Response;
            }

            $notifications = license_notification::whereIn('license_id', $licenseIds)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get()
                ->unique('email')
                ->values();

            if ($notifications->count() == 0) {
                $Response['message'] = 'No notification emails found';
                return $Response;
            }

            $Mails = [];
            foreach ($notifications as $notification) {
                $Mails[] = [
                    'address' => $notification->email,
                    'name' => $income->client_name
                ];
            }

            $MailData = [
                'subject' => 'Confirmación de pago recibido #' . substr($income->unique_id, -10)
            ];
            $View = 'mail.client_income_payment_thanks';
            $ViewData = collect([
                'income' => $income,
            ]);

            $mailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null, $income->unique_id);
            $Response['status'] = $mailResponse['status'];
            $Response['message'] = $mailResponse['message'];
            $Response['data'] = $mailResponse;
        } catch (\Exception $e) {
            info('Income_SendClientPaymentThanksEmail error: ' . $e->getMessage());
            $Response['message'] = 'Income_SendClientPaymentThanksEmail: ' . $e->getMessage();
        }
        return $Response;
    }
    //STATISTICS
    public function Income_StatisticGetIncomeValuesByMonth($date)
    {
        $date = Carbon::parse($date);
        $current_month = 0;
        $year_average = 0;
        $difference = 0;
        try {
            // Ingresos pagados SIN abonos: se cuenta income.total por payment_date del income
            $current_month_incomes = income::whereIn('state', [3, 4])
                ->whereDoesntHave('income_advances')
                ->whereMonth('payment_date', $date->format('m'))
                ->whereYear('payment_date', $date->format('Y'))
                ->sum('total');
            // Ingresos CON abonos (cualquier estado excepto rechazado): se cuentan los abonos por su payment_date
            $current_month_advances = income_advance::whereHas('income', function ($q) {
                $q->whereNotIn('state', [1])->whereNull('deleted_at');
            })
                ->whereMonth('payment_date', $date->format('m'))
                ->whereYear('payment_date', $date->format('Y'))
                ->sum('amount');
            $current_month = $current_month_incomes + $current_month_advances;

            // Calcular el promedio de los últimos 12 meses
            $start_date = $date->copy()->subMonths(12)->startOfMonth();
            $end_date = $date->copy()->subMonth()->endOfMonth();

            $last_12_months_incomes = income::whereIn('state', [3, 4])
                ->whereDoesntHave('income_advances')
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->sum('total');
            $last_12_months_advances = income_advance::whereHas('income', function ($q) {
                $q->whereNotIn('state', [1])->whereNull('deleted_at');
            })
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->sum('amount');
            $last_12_months_total = $last_12_months_incomes + $last_12_months_advances;

            // Contar los meses con datos en los últimos 12 meses
            $months_with_data_incomes = income::whereIn('state', [3, 4])
                ->whereDoesntHave('income_advances')
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->selectRaw('COUNT(DISTINCT DATE_FORMAT(payment_date, "%Y-%m")) as months_count')
                ->first()
                ->months_count;
            $months_with_data_advances = income_advance::whereHas('income', function ($q) {
                $q->whereNotIn('state', [1])->whereNull('deleted_at');
            })
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->selectRaw('COUNT(DISTINCT DATE_FORMAT(payment_date, "%Y-%m")) as months_count')
                ->first()
                ->months_count;
            $months_with_data = max($months_with_data_incomes, $months_with_data_advances);

            $year_average = $months_with_data > 0 ? $last_12_months_total / $months_with_data : 0;

            $difference_porcentage = ($year_average == 0 || $current_month == 0) ? 0 : (round((($current_month / $year_average) - 1) * 100, 2));
        } catch (\Exception $e) {
            info('Income_GetIncomeValuesByMonth error: ' . $e->getMessage());
        }
        return [
            'status' => 1,
            'data' => [
                'month' => $date->format('m'),
                'current_month' => number_format($current_month, 0, ',', '.'),
                'year_average' => number_format($year_average, 0, ',', '.'),
                'difference_porcentage' => $difference_porcentage
            ]
        ];
    }
    public function Income_StatisticGetIncomesByStatus($status)
    {
        try {
            //Ordenar por días vencidos (de mayor a menor) y luego por fecha de corte
            $incomes = income::where('state', $status)->orderBy('cutoff_date', 'asc')->get();
            
            // Ordenar la colección por días vencidos de mayor a menor
            $incomes = $incomes->sortByDesc(function($income) {
                return $income->days_overdue;
            })->values();
            
            $total_items = $incomes->count();
            $total_value = $incomes->sum('total');
            return [
                'status' => 1,
                'message' => 'Ingresos obtenidos',
                'data' => [
                    'incomes' => $incomes,
                    'total_items' => $total_items,
                    'total_value' => number_format($total_value, 0, ',', '.')
                ]
            ];
        } catch (\Exception $e) {
            info('Income_GetIncomesByStatus error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Income_StatisticGetIncomesByMonthRange($start_date, $end_date)
    {
        $Response = [
            'status' => 1,
            'message' => '',
            'data' => [
                'incomes_by_month' => [],
                'incomes_count' => 0,
                'incomes_average' => 0,
                'incomes_max' => 0,
                'incomes_min' => 0,
                'incomes_total' => 0,
                'month_labels' => []
            ]
        ];
        try {
            $start_date = Carbon::parse($start_date)->startOfMonth();
            $end_date = Carbon::parse($end_date)->endOfMonth();
            // Ingresos pagados SIN abonos: se cuenta income.total por payment_date del income
            $incomesQuery = income::whereIn('state', [3, 4])
                ->whereDoesntHave('income_advances')
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
            // Ingresos CON abonos (cualquier estado excepto rechazado): se cuentan los abonos por su payment_date
            $advancesQuery = income_advance::whereHas('income', function ($q) {
                $q->whereNotIn('state', [1])->whereNull('deleted_at');
            })
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
            $Response['data']['incomes_count'] = $incomesQuery->count();
            $Response['data']['incomes_total'] = round($incomesQuery->sum('total') + $advancesQuery->sum('amount'));
            $Response['data']['incomes_total_string'] = number_format($Response['data']['incomes_total'], 0, ',', '.');
            $incomes = $incomesQuery->get();
            $advances = $advancesQuery->get();
            //get months range name
            $current_date = $start_date;
            while ($current_date <= $end_date) {
                $month_start = $current_date->format('Y-m-d');
                $month_end = $current_date->copy()->endOfMonth()->format('Y-m-d');
                $Response['data']['month_labels'][] = strtoupper($current_date->format('M'));
                $incomes_sum = $incomes->where('payment_date', '>=', $month_start)->where('payment_date', '<=', $month_end)->sum('total');
                $advances_sum = $advances->where('payment_date', '>=', $month_start)->where('payment_date', '<=', $month_end)->sum('amount');
                $Response['data']['incomes_by_month'][] = round($incomes_sum + $advances_sum);
                $current_date = $current_date->addMonth();
            }
            $Response['data']['incomes'] = $incomes;
            $Response['data']['incomes_by_month'] = collect($Response['data']['incomes_by_month']);
            $Response['data']['incomes_max'] = round($Response['data']['incomes_by_month']->max());
            $Response['data']['incomes_min'] = round($Response['data']['incomes_by_month']->min());
            $Response['data']['incomes_average'] = round($Response['data']['incomes_by_month']->avg());
            $Response['data']['incomes_average_string'] = number_format($Response['data']['incomes_average'], 0, ',', '.');
        } catch (\Exception $e) {
            info('Income_GetIncomesByMonthRange error: ' . $e->getMessage());
        }
        return $Response;
    }
    public function Income_StatisticGetSalesByMonthRange($start_date, $end_date)
    {
        $Response = [
            'status' => 1,
            'message' => '',
            'data' => [
                'incomes_by_month' => [],
                'incomes_count' => 0,
                'incomes_average' => 0,
                'incomes_max' => 0,
                'incomes_min' => 0,
                'incomes_total' => 0,
                'month_labels' => []
            ]
        ];
        try {
            $start_date = Carbon::parse($start_date)->startOfMonth();
            $end_date = Carbon::parse($end_date)->endOfMonth();
            $incomes = income::
                whereIn('state', [3, 4])
                ->whereHas('income_licenses', function ($query) {
                    $query
                        ->whereHas('license', function ($query) {
                            $query
                                ->where('type', '2')
                            ;
                        })
                        ->with('license')
                    ;
                })
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
            $Response['data']['incomes_count'] = $incomes->count();
            $Response['data']['incomes_total'] = round($incomes->sum('total'));
            $incomes = $incomes->get();
            //get months range name
            $current_date = $start_date;
            while ($current_date <= $end_date) {
                $Response['data']['month_labels'][] = strtoupper($current_date->format('M'));
                $Response['data']['incomes_by_month'][] = round($incomes->where('payment_date', '>=', $current_date->format('Y-m-d'))->where('payment_date', '<=', $current_date->copy()->endOfMonth()->format('Y-m-d'))->sum(function ($item) {
                    return $item->income_licenses->where('license.type', '2')->sum('total');
                }));
                $current_date = $current_date->addMonth();
            }
            $Response['data']['incomes'] = $incomes;
            $Response['data']['incomes_by_month'] = collect($Response['data']['incomes_by_month']);
            $Response['data']['incomes_max'] = round($Response['data']['incomes_by_month']->max());
            $Response['data']['incomes_min'] = round($Response['data']['incomes_by_month']->min());
            $Response['data']['incomes_average'] = round($Response['data']['incomes_by_month']->avg());
        } catch (\Exception $e) {
            info('Income_GetSalesByMonthRange error: ' . $e->getMessage());
        }
        return $Response;
    }
    public function Income_StatisticGetIncomesByClientDateRange($start_date, $end_date)
    {
        $Response = [
            'status' => 1,
            'message' => '',
            'data' => [
                'clients' => [],
                'client_labels' => [],
                'incomes_by_client' => [],
                'incomes_total' => 0,
                'incomes_count' => 0
            ]
        ];
        try {
            $start_date = Carbon::parse($start_date)->startOfMonth();
            $end_date = Carbon::parse($end_date)->endOfMonth();
            
            // Ingresos pagados SIN abonos: se cuenta income.total por payment_date del income
            $total_paid_incomes = income::whereIn('state', [3, 4])
                ->whereDoesntHave('income_advances')
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->sum('total');
            // Ingresos CON abonos (cualquier estado excepto rechazado): se cuentan los abonos por su payment_date
            $total_advances = income_advance::whereHas('income', function ($q) {
                $q->whereNotIn('state', [1])->whereNull('deleted_at');
            })
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->sum('amount');

            // Obtener ingresos pagados SIN abonos agrupados por cliente
            $paid_by_client = income::whereIn('state', [3, 4])
                ->whereDoesntHave('income_advances')
                ->whereBetween('payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->selectRaw('client_id, client_name, SUM(total) as total_income')
                ->groupBy('client_id', 'client_name')
                ->get()
                ->keyBy('client_id');

            // Obtener abonos de ingresos NO rechazados (con abonos) agrupados por cliente
            $advances_by_client = income_advance::join('incomes', 'income_advances.income_id', '=', 'incomes.id')
                ->whereNotIn('incomes.state', [1])
                ->whereNull('incomes.deleted_at')
                ->whereBetween('income_advances.payment_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->selectRaw('incomes.client_id, incomes.client_name, SUM(income_advances.amount) as total_income')
                ->groupBy('incomes.client_id', 'incomes.client_name')
                ->get()
                ->keyBy('client_id');

            // Fusionar ambas colecciones por cliente
            $merged = collect();
            foreach ($paid_by_client as $client_id => $item) {
                $adv = $advances_by_client->get($client_id);
                $merged->put($client_id, [
                    'client_name' => $item->client_name,
                    'total_income' => $item->total_income + ($adv ? $adv->total_income : 0)
                ]);
            }
            foreach ($advances_by_client as $client_id => $item) {
                if (!$merged->has($client_id)) {
                    $merged->put($client_id, [
                        'client_name' => $item->client_name,
                        'total_income' => $item->total_income
                    ]);
                }
            }
            $incomes = $merged->sortByDesc('total_income')->take(10)->values();

            $Response['data']['incomes_count'] = $incomes->count();
            $Response['data']['incomes_total'] = round($total_paid_incomes + $total_advances);
            $Response['data']['incomes_total_string'] = number_format($Response['data']['incomes_total'], 0, ',', '.');

            foreach ($incomes as $income) {
                $Response['data']['client_labels'][] = $income['client_name'];
                $Response['data']['incomes_by_client'][] = round($income['total_income']);
            }
            
            $Response['data']['clients'] = $incomes;
        } catch (\Exception $e) {
            info('Income_StatisticGetIncomesByClientDateRange error: ' . $e->getMessage());
        }
        return $Response;
    }
    public function Income_GetIncomesByStateDateRangeReport(
        $date_from
        ,
        $date_to
        ,
        $states
    ) {
        $Reponse = [
            'status' => 0,
            'message' => 'No se encontraron usuarios',
            'data' => []
        ];
        try {
            $date_from = Carbon::parse($date_from);
            $date_to = Carbon::parse($date_to);
            $Reponse = $this->Income_GetIncomesByStateDateRange(
                $date_from
                ,
                $date_to
                ,
                $states
            );
            if ($Reponse['status'] == 0) {
                return $Reponse;
            }
            $incomes = $Reponse['data'];
            $date_diff = $date_to->diffInDays($date_from);
            if ($date_diff < 31) {
                $report = $incomes->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('d M Y');
                })->map(function ($grupped_incomes) {
                    // Return the count of incomes per day
                    return [
                        'label' => $grupped_incomes->first()->created_at->format('d M Y') . ' - ' . $grupped_incomes->sum('total'),
                        'total' => $grupped_incomes->sum('total'),
                    ];
                });
                $all_days = collect();
                $current_day = $date_from->copy();
                while ($current_day->lessThanOrEqualTo($date_to)) {
                    $all_days->put($current_day->format('Y-m-d'), [
                        'label' => $current_day->format('d M Y'),
                        'total' => 0
                    ]);
                    $current_day->addDay();
                }
                $report = $all_days->map(function ($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            } else if ($date_diff < 365) {
                //sum income by month
                $report = $incomes->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('M Y');
                })->map(function ($grupped_incomes) {
                    // Return the count of incomes per month
                    return [
                        'label' => $grupped_incomes->first()->created_at->format('M Y') . ' - ' . $grupped_incomes->sum('total'),
                        'total' => $grupped_incomes->sum('total'),
                    ];
                });
                // Generate an array of all months within the range
                $all_months = collect();
                $current_month = $date_from->copy();
                while ($current_month->lessThanOrEqualTo($date_to)) {
                    $all_months->put($current_month->format('Y-m'), [
                        'label' => $current_month->format('M Y'),
                        'total' => 0
                    ]);
                    $current_month->addMonth();
                }
                $report = $all_months->map(function ($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            } else {
                //sum income by year
                $report = $incomes->groupBy(function ($date) {
                    return Carbon::parse($date->created_at)->format('Y');
                })->map(function ($grupped_incomes) {
                    // Return the count of incomes per year
                    return [
                        'label' => $grupped_incomes->first()->created_at->format('Y') . ' - ' . $grupped_incomes->sum('total'),
                        'total' => $grupped_incomes->sum('total'),
                    ];
                });
                // Generate an array of all years within the range
                $all_years = collect();
                $current_year = $date_from->copy();
                while ($current_year->lessThanOrEqualTo($date_to)) {
                    $all_years->put($current_year->format('Y'), [
                        'label' => $current_year->format('Y'),
                        'total' => 0
                    ]);
                    $current_year->addYear();
                }
                $report = $all_years->map(function ($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }
            Storage::disk('reports')->put('incomes' . Session::get('user')['unique_id'] . '.json', json_encode($incomes));
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de empleados obtenido',
                'data' => [
                    'incomes' => $incomes,
                    'report' => $report
                ]
            ];
        } catch (\Exception $e) {
            info('Income_GetIncomesByStateDateRangeReport error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function Income_GetIncomesByStateDateRange(
        $date_from
        ,
        $date_to
        ,
        $states
    ) {
        try {
            $incomes = income::
                with([
                    'client',
                    'income_licenses' => function ($query) {
                        $query->with(['employee']);
                    }
                ])
                ->where(function ($query) use ($date_from, $date_to) {
                    $query->where(function ($query) use ($date_from, $date_to) {
                        $query->whereDate('created_at', '>=', $date_from)
                            ->whereDate('created_at', '<=', $date_to);
                    });
                    $query->orWhere(function ($query) use ($date_from, $date_to) {
                        $query->whereDate('payment_date', '>=', $date_from)
                            ->whereDate('payment_date', '<=', $date_to);
                    });
                })
                ->whereIn('state', $states)
                ->orderBy('created_at', 'asc')
                ->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $incomes
            ];
        } catch (\Exception $e) {
            info('Income_GetIncomesByStateDateRange error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Income_GetIncomesPayedByDateRangeReport(
        $date_from
        ,
        $date_to
    ) {
        $Reponse = [
            'status' => 0,
            'message' => 'No se encontraron usuarios',
            'data' => []
        ];
        try {
            $date_from = Carbon::parse($date_from)->startOfDay();
            $date_to = Carbon::parse($date_to)->endOfDay();
            $Reponse = $this->Income_GetIncomesPayedByDateRange(
                $date_from
                ,
                $date_to
            );
            if ($Reponse['status'] == 0) {
                return $Reponse;
            }
            $incomes = $Reponse['data'];
            $date_diff = $date_to->diffInDays($date_from);
            if ($date_diff < 31) {
                $report = $incomes->groupBy(function ($date) {
                    return Carbon::parse($date->payment_date)->format('d M Y');
                })->map(function ($grupped_incomes) {
                    // Return the count of incomes per day
                    return [
                        'label' => Carbon::parse($grupped_incomes->first()->payment_date)->format('d M Y') . ' - ' . $grupped_incomes->sum('total'),
                        'total' => $grupped_incomes->sum('total'),
                    ];
                });
                $all_days = collect();
                $current_day = $date_from->copy();
                while ($current_day->lessThanOrEqualTo($date_to)) {
                    $all_days->put($current_day->format('Y-m-d'), [
                        'label' => $current_day->format('d M Y'),
                        'total' => 0
                    ]);
                    $current_day->addDay();
                }
                $report = $all_days->map(function ($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            } else if ($date_diff < 365) {
                //sum income by month
                $report = $incomes->groupBy(function ($date) {
                    return Carbon::parse($date->payment_date)->format('M Y');
                })->map(function ($grupped_incomes) {
                    // Return the count of incomes per month
                    return [
                        'label' => Carbon::parse(Carbon::parse($grupped_incomes->first()->payment_date))->format('M Y') . ' - ' . $grupped_incomes->sum('total'),
                        'total' => $grupped_incomes->sum('total'),
                    ];
                });
                // Generate an array of all months within the range
                $all_months = collect();
                $current_month = $date_from->copy();
                while ($current_month->lessThanOrEqualTo($date_to)) {
                    $all_months->put($current_month->format('Y-m'), [
                        'label' => $current_month->format('M Y'),
                        'total' => 0
                    ]);
                    $current_month->addMonth();
                }
                $report = $all_months->map(function ($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            } else {
                //sum income by year
                $report = $incomes->groupBy(function ($date) {
                    return Carbon::parse($date->payment_date)->format('Y');
                })->map(function ($grupped_incomes) {
                    // Return the count of incomes per year
                    return [
                        'label' => Carbon::parse($grupped_incomes->first()->payment_date)->format('Y') . ' - ' . $grupped_incomes->sum('total'),
                        'total' => $grupped_incomes->sum('total'),
                    ];
                });
                // Generate an array of all years within the range
                $all_years = collect();
                $current_year = $date_from->copy();
                while ($current_year->lessThanOrEqualTo($date_to)) {
                    $all_years->put($current_year->format('Y'), [
                        'label' => $current_year->format('Y'),
                        'total' => 0
                    ]);
                    $current_year->addYear();
                }
                $report = $all_years->map(function ($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de empleados obtenido',
                'data' => [
                    'incomes' => $incomes,
                    'report' => $report
                ]
            ];
        } catch (\Exception $e) {
            info('Income_GetIncomesPayedByDateRangeReport error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function Income_GetIncomesPayedByDateRange(
        $date_from
        ,
        $date_to
    ) {
        try {
            //$payed incomes 
            $incomes = income::
                with([
                    'client',
                    'income_licenses' => function ($query) {
                        $query->with(['employee']);
                    }
                ])
                ->whereDate('payment_date', '>=', $date_from)
                ->whereDate('payment_date', '<=', $date_to)
                ->whereIn('state', [3, 4])
                ->orderBy('payment_date', 'asc')
                ->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $incomes
            ];
        } catch (\Exception $e) {
            info('Income_GetIncomesPayedByDateRange error: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Income_UpdateIncomeState(
        $income_id
        ,
        $state
    ) {
        $Response = array(
            'status' => 0,
            'message' => 'Error',
            'data' => null
        );
        try {
            $income = income::
                with([
                    'client' => function ($query) {
                        $query->with(['country']);
                    }
                ])
                ->find($income_id);
            if ($income == null) {
                $Response['message'] = 'Income not found';
                return $Response;
            }

            $income->state = $state;
            $income->save();

            //update pdf and qr
            $url = url('/') . '/client/payments/pay/' . $income->unique_id;

            if ($income->state == 2 || $income->state == 3 || $income->state == 4) {
                QrCode::format('png')->size(500)->generate($url, Storage::disk('incomes_qr')->path($income->unique_id . '.png'));
                $pdfResponse = $this->Income_CreateOrderPurchasePdf($income, $income->client);
            } else {
                $pdfResponse = $this->Income_CreateOrderQuotationPdf($income, $income->client);
            }

            $Response['status'] = 1;
            $Response['message'] = 'Income state updated';
            $Response['data'] = $income;
        } catch (\Exception $e) {
            info('Income_UpdateIncomeState error: ' . $e->getMessage());
            $Response['message'] = 'Income_UpdateIncomeState: ' . $e->getMessage();
        }
        return $Response;
    }
    
    /**
     * Get overdue static incomes that need payment reminders
     * Sends reminders on: day of expiration, 5 days after, and 10+ days after (daily)
     */
    public function Income_GetOverdueStaticIncomes()
    {
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => collect()
        ];
        
        try {
            $today = Carbon::now()->startOfDay();
            
            // Get all overdue incomes from static licenses (2 = approved, not paid)
            $incomes = income::query()
                // Only unpaid invoices
                ->whereIn('state', [2])
                // Only where cutoff_date has passed
                ->where('cutoff_date', '<', $today)
                // Load necessary relationships
                ->with([
                    'client' => function($query) {
                        $query->where('active', 1);
                    },
                    'income_licenses.license' => function($query) {
                        // Only static licenses (type = 2)
                        $query->where('type', 2);
                    },
                    'income_licenses'
                ])
                // Only incomes that have at least one static license
                ->whereHas('income_licenses.license', function($query) {
                    $query->where('type', 2);
                })
                // Only incomes from active clients
                ->whereHas('client', function($query) {
                    $query->where('active', 1);
                })
                ->get();
            
            // Calculate days overdue for each income
            $incomes->each(function($income) use ($today) {
                $cutoffDate = Carbon::parse($income->cutoff_date)->startOfDay();
                $income->days_overdue = $today->diffInDays($cutoffDate);
            });
            
            $Response['status'] = 1;
            $Response['message'] = 'Overdue static incomes retrieved';
            $Response['data'] = $incomes;
            
        } catch (\Exception $e) {
            info('Income_GetOverdueStaticIncomes error: ' . $e->getMessage());
            $Response['message'] = 'Income_GetOverdueStaticIncomes: ' . $e->getMessage();
        }
        
        return $Response;
    }

    /**
     * Get all overdue incomes (both static and recurring) for payment reminders
     * Applies reminder logic: day 0 (expiration), day 5, and day 10+ (every day)
     */
    public function Income_GetAllOverdueIncomes()
    {
        $Response = [
            'status' => 0,
            'message' => 'Error',
            'data' => collect()
        ];
        
        try {
            $today = Carbon::now()->startOfDay();
            
            // Get all overdue incomes regardless of license type (2 = approved, not paid)
            $incomes = income::query()
                // Only unpaid invoices
                ->whereIn('state', [2])
                // Only where cutoff_date has passed
                ->where('cutoff_date', '<', $today)
                // Load necessary relationships
                ->with([
                    'client' => function($query) {
                        $query->where('active', 1);
                    },
                    'income_licenses.license',
                    'income_licenses'
                ])
                // Only incomes that have licenses
                ->whereHas('income_licenses.license')
                // Only incomes from active clients
                ->whereHas('client', function($query) {
                    $query->where('active', 1);
                })
                ->get();
            
            // Filter based on reminder logic: day 0 (expiration), day 5, and day 10+ (every day)
            /*
            $incomesToRemind = $incomes->filter(function($income) use ($today) {
                $cutoffDate = Carbon::parse($income->cutoff_date)->startOfDay();
                $daysOverdue = $today->diffInDays($cutoffDate);
                
                // Add days_overdue to the income object
                $income->days_overdue = $daysOverdue;
                
                // Send reminder on:
                // 1. Day of expiration (daysOverdue = 0)
                // 2. 5 days after expiration (daysOverdue = 5)
                // 3. Every day from day 10 onwards (daysOverdue >= 10)
                return $daysOverdue == 0 || $daysOverdue == 5 || $daysOverdue >= 10;
            });
            */
            $Response['status'] = 1;
            $Response['message'] = 'All overdue incomes retrieved';
            $Response['data'] = $incomes;
            
        } catch (\Exception $e) {
            info('Income_GetAllOverdueIncomes error: ' . $e->getMessage());
            $Response['message'] = 'Income_GetAllOverdueIncomes: ' . $e->getMessage();
        }
        
        return $Response;
    }
}