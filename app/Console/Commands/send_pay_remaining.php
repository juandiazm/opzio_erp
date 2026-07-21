<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use App\traits\licenses_trait;
use App\traits\open_ia_trait;
use App\traits\mail_trait;
use App\traits\twilio_sms_trait;
use App\traits\incomes_trait;

class send_pay_remaining extends Command
{
    use 
    licenses_trait
    ,open_ia_trait
    ,mail_trait
    ,twilio_sms_trait
    ,incomes_trait
    ;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:send_pay_remaining';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pay remaining to all users who have not paid yet.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get all overdue incomes (both static and recurring)
        $incomesResponse = $this->Income_GetAllOverdueIncomes();
        $incomes = $incomesResponse['data'];
        info('command:send_pay_remaining - Total overdue incomes found: ' . count($incomes));
        
        $ia_messages = [];
        $report_message = [];
        
        // Group all incomes by client
        $groupedByClient = [];
        
        // Process all incomes
        foreach($incomes as $income) {
            $client = $income->client;
            
            // Skip if client is not active
            if(!$client || $client->active != 1) {
                continue;
            }
            
            $client_id = $client->id;
            
            if(!isset($groupedByClient[$client_id])) {
                $groupedByClient[$client_id] = [
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'identification' => $client->identification,
                    ],
                    'incomes' => [],
                    'income_ids' => [], // Track unique income IDs
                    'emails' => [],
                    'phones' => [],
                    'service_names' => [],
                ];
            }
            
            // Check if this income is already added (avoid duplicates)
            if(!in_array($income->unique_id, $groupedByClient[$client_id]['income_ids'])){
                // Add income to client group
                $groupedByClient[$client_id]['incomes'][] = [
                    'unique_id' => $income->unique_id,
                    'client_name' => $income->client_name,
                    'client_identification' => $income->client_identification,
                    'timely_payment' => $income->timely_payment,
                    'cutoff_date' => $income->cutoff_date,
                    'total' => $income->total,
                    'payment_link' => $income->payment_link,
                    'state' => $income->state,
                    'days_overdue' => $income->days_overdue,
                    'siigo_invoice_url' => $income->siigo_invoice_url,
                    'has_electronic_invoice' => !empty($income->siigo_invoice_url),
                ];
                
                // Track this income ID
                $groupedByClient[$client_id]['income_ids'][] = $income->unique_id;
                info('command:send_pay_remaining - Added income: ' . $income->unique_id . ' (days overdue: ' . $income->days_overdue . ') for client: ' . $client->name);
            }else{
                info('command:send_pay_remaining - Duplicate income skipped: ' . $income->unique_id . ' for client: ' . $client->name);
            }
            
            // Get notification emails and phones from licenses associated with this income
            $license_ids = $income->income_licenses->pluck('license_id')->toArray();
            $notificationsResponse = $this->License_GetLicenseNotificationsByLicensesIds($license_ids);
            
            if($notificationsResponse['status'] == 1){
                foreach($notificationsResponse['data'] as $item){
                    if($item['email'] != null && $item['email'] != ''){
                        $groupedByClient[$client_id]['emails'][] = $item['email'];
                    }
                    if($item['phone'] != null && $item['phone'] != ''){
                        $groupedByClient[$client_id]['phones'][] = $item['phone'];
                    }
                }
            }
            
            // Collect service names for IA message
            foreach($income->income_licenses as $incomeLicense){
                if($incomeLicense->license && isset($incomeLicense->license->service)){
                    $serviceName = $incomeLicense->license->service->name ?? 'nuestros servicios';
                    if(!in_array($serviceName, $groupedByClient[$client_id]['service_names'])){
                        $groupedByClient[$client_id]['service_names'][] = $serviceName;
                    }
                }
            }
        }
        
        // Send one email per client with all their incomes
        foreach($groupedByClient as $client_id => $clientData) {
            info('command:send_pay_remaining - Processing client: ' . $clientData['client']['name'] . ' with ' . count($clientData['incomes']) . ' income(s)');
            
            try{
                // Generate IA message using the first service name
                $firstServiceName = !empty($clientData['service_names']) ? $clientData['service_names'][0] : 'nuestros servicios';
                
                if(!array_key_exists($firstServiceName, $ia_messages)){
                    $ResponseIA = $this->OpenIA_MakeQuestion(
                        'Eres una empresa de software, debes escribir un mensaje publicitario hacia uno de tus clientes usando tuteo (tú). Debes incentivar al cliente a consumir los servicios de '.$firstServiceName.' Y explicarle por qué este servicio agrega valor a sus negocios. No más de 150 caracteres. Usa un lenguaje profesional y cercano tuteando, no agregues hashtags, sin llamados a la acción.'
                    );
                    if($ResponseIA['status']==1){
                        $ia_message = $ResponseIA['data'][0];
                    }else{
                        $ia_message = 'Optimiza tu productividad y eficiencia con nuestra solución de software: automatización inteligente para simplificar tus procesos comerciales.';
                    }
                    $ia_messages[$firstServiceName] = $ia_message;
                }else{
                    $ia_message = $ia_messages[$firstServiceName];
                }
                
                // Prepare emails
                $uniqueEmails = array_unique($clientData['emails']);
                $Mails = [];
                foreach($uniqueEmails as $email){
                    $Mails[] = [
                        'address' => $email,
                        'name' => $email,
                    ];
                }
                
                if(count($Mails) > 0){
                    // Prepare attachments - all PDFs for this client
                    $attachments = [];
                    foreach($clientData['incomes'] as $income){
                        $pdfPath = storage_path('app/public/incomes/pdfs/' . $income['unique_id'] . '.pdf');
                        if(file_exists($pdfPath)){
                            $order_id = substr($income['unique_id'], -10);
                            $attachments[] = [
                                'path' => $pdfPath,
                                'name' => ($income['state']==2?'Orden de compra':'Cotización') . '_' . $order_id.'.pdf'
                            ];
                        }
                    }
                    
                    // Check if all incomes have electronic invoices
                    $hasElectronicInvoice = collect($clientData['incomes'])->every(function($income) {
                        return $income['has_electronic_invoice'];
                    });
                    
                    // Prepare subject based on invoice type
                    $invoiceCount = count($clientData['incomes']);
                    if($hasElectronicInvoice){
                        $subject = $invoiceCount > 1 
                            ? 'Tienes ' . $invoiceCount . ' facturas electrónicas pendientes' 
                            : 'Recuerda realizar tu pago - Factura #' . substr($clientData['incomes'][0]['unique_id'], -10);
                    } else {
                        $subject = $invoiceCount > 1 
                            ? 'Tienes ' . $invoiceCount . ' órdenes de compra pendientes' 
                            : 'Recuerda realizar tu pago #' . substr($clientData['incomes'][0]['unique_id'], -10);
                    }
                    
                    $MailData = [
                        'subject' => $subject,
                    ];
                    
                    // Select appropriate view based on invoice type
                    $View = $hasElectronicInvoice ? 'mail.pay_remaining_grouped_invoice' : 'mail.pay_remaining_grouped';
                    $ViewData = collect([
                        'client' => $clientData['client'],
                        'incomes' => $clientData['incomes'],
                        'ia_message' => $ia_message,
                    ]);
                    
                    $MailResponse = $this->SendMail_attach_array($MailData, $Mails, $View, $ViewData, $attachments);
                }
            }catch(\Exception $e) {
                info('command:send_pay_remaining email grouped: '.$e->getMessage());
            }
            
            // Send SMS for each income (keeping individual SMS as they have character limits)
            try{
                $uniquePhones = array_unique($clientData['phones']);
                
                // Check if all incomes have electronic invoices
                $hasElectronicInvoice = collect($clientData['incomes'])->every(function($income) {
                    return $income['has_electronic_invoice'];
                });
                
                foreach($uniquePhones as $phone){
                    // Send one SMS with summary if multiple incomes, or detailed if just one
                    if(count($clientData['incomes']) > 1){
                        $totalAmount = array_sum(array_column($clientData['incomes'], 'total'));
                        $documentType = $hasElectronicInvoice ? 'facturas electrónicas' : 'órdenes de compra';
                        $MessageValue = 'Hola '.$clientData['client']['name'].', tienes '.count($clientData['incomes']).' '.$documentType.' pendientes por un total de COP $'.number_format($totalAmount, 0,',','.').'. Revisa tu correo para más detalles y enlaces de pago.';
                    }else{
                        $income = $clientData['incomes'][0];
                        $order_id = substr($income['unique_id'], -10);
                        $documentType = $hasElectronicInvoice ? 'Factura' : 'Orden de compra';
                        $MessageValue = 'Hola '.$clientData['client']['name'].', generamos la '.$documentType.' #'.$order_id.' por un valor de COP $'.number_format($income['total'], 0,',','.').'. Paga antes del '.$income['cutoff_date'].' en '.$income['payment_link'];
                    }
                    $Response = $this->TwilioSMS_SendMessage('+57', $phone, $MessageValue);
                }
            }catch(\Exception $e) {
                info('command:send_pay_remaining sms grouped: '.$e->getMessage());
            }
            
            // Add each unique income to report (no duplicates)
            foreach($clientData['incomes'] as $income){
                $report_message[] = [
                    'client' => $clientData['client']['name'],
                    'identification' => $clientData['client']['identification'],
                    'total' => $income['total'],
                    'order_id' => substr($income['unique_id'], -10),
                    'days_overdue' => $income['days_overdue'],
                    'siigo_invoice_url' => $income['siigo_invoice_url'] ?? null,
                ];
            }
        }
        
        //send email report to admin
        if(count($report_message) > 0){
            $Mails = [
                [
                    'address' => 'juandiazm@opzio.co',
                    'name' => 'Juan Diaz',
                ]
            ];
            $MailData = 
            [
                'subject' => 'Reporte de envíos de recordatorio de pago',
            ];
            $View = 'mail.pay_remaining_report';
            $ViewData = collect(
            [
                'report_message' => $report_message,
            ]
            );
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            if($MailResponse['status'] == 1){
                info('command:send_pay_remaining report: Email sent successfully');
            }else{
                info('command:send_pay_remaining report: Error sending email: '.$MailResponse['message']);
            }
        }
        return 0;
    }
}
