<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

use App\traits\old_opzio_trait;
use App\traits\clients_trait;
use App\traits\licenses_trait;
use App\traits\incomes_trait;
use App\traits\outcomes_trait;

use App\Models\client_user_traceability;
use App\Models\client_user_permission_assoc;
use App\Models\client_user_assoc;
use App\Models\client_user;
use App\Models\client_document;
use App\Models\income_payment;
use App\Models\income_license;
use App\Models\license_notification;
use App\Models\license_document;
use App\Models\income;
use App\Models\license;
use App\Models\client;
use App\Models\country;
use App\Models\sector;
use App\Models\service;
use App\Models\tax;


class old_opzio_controller extends Controller
{
    use
        old_opzio_trait
        , clients_trait
        , licenses_trait
        , incomes_trait
        , outcomes_trait
    ;
    //
    public function get_and_set_client_and_licenses(Request $request)
    {
        if (App::environment() === 'local') {
            try {

                set_time_limit(0);
                //////////////////////////////////////////////////

                //////////////////////////////////////////////////
                $url = 'companies/get-all';
                $send_data = [];
                $Response = $this->Opzio_syh_PostRequest($url, $send_data);
                info($Response);
                if ($Response['status'] == 1) {
                    $Clients = $Response['data'];
                    $clientsDB = client::all();
                    $updatedLog = [];
                    foreach ($Clients as $Client) {
                        $ClientDB = $clientsDB->where('identification', $Client['nit'])->first();
                        if ($ClientDB) {
                            $ClientDB->verified_date = $Client['created_at'];
                            $ClientDB->save();
                            $updatedLog[] = $ClientDB->identification;
                        }
                    }
                    set_time_limit(60);
                    return $updatedLog;
                    //return $Clients;
                }
                return \Response::json($Response, 400);
            } catch (\Exception $e) {
                set_time_limit(60);
                info('opzio_syh_trait ' . $e->getMessage());
                return ['status' => 0, 'message' => $e->getMessage()];
            }
        } else {
            return ['status' => 0, 'message' => 'No permitido'];
        }

    }
    public function set_incomes(Request $request)
    {
        if (App::environment() === 'local') {
            try {

                set_time_limit(0);
                //////////////////////////////////////////////////

                //////////////////////////////////////////////////
                $url = 'bills/get-all';
                $send_data = [];
                $Response = $this->Opzio_syh_PostRequest($url, $send_data);
                if ($Response['status'] == 1) {
                    $Bills = $Response['data'];
                    $clientsDB = client::with('licenses')->get();
                    $licensesDB = license::with('service')->get();
                    $createdIncomes = [];
                    foreach ($Bills as $Bill) {
                        if ($Bill['client'] != null && $Bill['contract'] != null) {
                            $clientDB = $clientsDB->where('identification', $Bill['client']['nit'])->first();
                            if ($clientDB) {
                                $licenseDB = $licensesDB->where('id', $Bill['contract']['id'])->first();
                                if ($licenseDB) {
                                    switch ($Bill['state']) {
                                        case 0:
                                            $state = 1;
                                            break;
                                        case 1:
                                            $state = 3;
                                            break;
                                        case 2:
                                            $state = 1;
                                            break;
                                        default:
                                            $state = 1;
                                            break;
                                    }
                                    $generated_date = Carbon::parse($Bill['generated_date']);
                                    $income_data = [
                                        'state' => $state,
                                        'client_id' => $clientDB->id,
                                        'client_identification' => $clientDB->identification,
                                        'client_name' => $clientDB->name,
                                        'timely_payment' => $generated_date->format('Y-m-d'),
                                        'cutoff_date' => $generated_date->copy()->addDays(15)->format('Y-m-d'),
                                        'description' => $Bill['description'],
                                        'bill_name' => Str::uuid()->toString(),
                                        'bill_final_value' => $Bill['value'],
                                        'licenses' => [
                                            [
                                                'license_id' => $licenseDB->id,
                                                'license_name' => $licenseDB->name,
                                                'timely_payment' => $generated_date->format('Y-m-') . $licenseDB->billing_day,
                                                'service_id' => $licenseDB->service_id,
                                                'service_name' => $licenseDB->service->name,
                                                'recurrence_months' => $licenseDB->recurrence_months,
                                                'value' => $Bill['value'],
                                                'comission' => 0,
                                                'employee_id' => null,
                                                'employee_name' => null,
                                                'tax_id' => null,
                                                'tax_name' => null,
                                                'tax_value' => 0,
                                                'description' => $Bill['description'],
                                                'total' => $Bill['value'],
                                                'hours' => 0,
                                            ]
                                        ]
                                    ];
                                    $incomResult = $this->Income_Createincome(
                                        $income_data['state'],
                                        $income_data['client_id'],
                                        $income_data['client_identification'],
                                        $income_data['client_name'],
                                        $income_data['timely_payment'],
                                        $income_data['cutoff_date'],
                                        $income_data['description'],
                                        $income_data['licenses'],
                                        $income_data['bill_name'],
                                        $income_data['bill_final_value'],
                                        $generated_date
                                        
                                    );
                                    if($incomResult['status'] == 1 && $state == 3){
                                        $income = $incomResult['data']['income'];
                                        $this->Income_UpdateIncomePaymentData(
                                            
                                            $income['id']
                                            ,1
                                            ,$Bill['paid_date']
                                            ,$income_data['bill_name']
                                            ,$income_data['bill_name']
                                            ,$income_data['bill_final_value']
                                        );
                                    }
                                    $createdIncomes[] = [
                                        'income_data' => $income_data,
                                        'result' => $incomResult
                                    ];
                                }
                            }
                        }
                    }
                    set_time_limit(60);
                    return $createdIncomes;
                    //return $Clients;
                }
                return \Response::json($Response, 400);
            } catch (\Exception $e) {
                set_time_limit(60);
                info('opzio_syh_trait ' . $e->getMessage());
                return ['status' => 0, 'message' => $e->getMessage()];
            }
        } else {
            return ['status' => 0, 'message' => 'No permitido'];
        }

    }
    public function set_outcomes(Request $request)
    {
        if (App::environment() === 'local') {
            try {

                set_time_limit(0);
                //////////////////////////////////////////////////

                //////////////////////////////////////////////////
                $url = 'expenses/get-all';
                $send_data = [];
                $Response = $this->Opzio_syh_PostRequest($url, $send_data);
                if ($Response['status'] == 1) {
                    $Expenses = $Response['data'];
                    $ExpensesResult=[];
                    foreach($Expenses as $Expense){
                        $date = Carbon::parse($Expense['generated_date']);
                        $name = $Expense['name'];
                        $description = $Expense['description']==null?'':$Expense['description'];
                        $amount = $Expense['value'];
                        $ExpensesResult[] = $this->Outcome_CreateOutcome(
                            $date
                            ,$name
                            ,$description
                            ,$amount
                            ,-1
                            ,1
                            ,null
                        );
                    }
                    return $ExpensesResult;
                }
            }
            catch (\Exception $e) {
                set_time_limit(60);
                info('opzio_syh_trait ' . $e->getMessage());
                return ['status' => 0, 'message' => $e->getMessage()];
            }
        }else{
            return ['status' => 0, 'message' => 'No permitido'];
        }
    }
    public function last_payed_date_on_licenses(Request $request)
    {
        $url = 'companies/get-all';
        $send_data = [];
        $Response = $this->Opzio_syh_PostRequest($url, $send_data);

        if ($Response['status'] == 1) {

            $Contracts = $Response['contracts'];
            foreach ($Contracts as $Contract) {
                $License = license::find($Contract['id']);
                if ($License && $Contract['description'] != null) {
                    $License->description = $Contract['description'];
                    $License->save();
                }
            }
        }
        return $Response;
    }
}
