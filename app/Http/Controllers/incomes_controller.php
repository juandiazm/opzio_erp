<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\income;

use App\traits\incomes_trait;
class incomes_controller extends Controller
{
    use incomes_trait;
    //Add Income
    public function create_income(Request $request){
        $Response = $this->Income_Createincome(
            $request->state,
            $request->client_id,
            $request->client_identification,
            $request->client_name,
            $request->timely_payment,
            $request->cutoff_date,
            $request->description,
            $request->licenses
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }

    public function importMassiveQuotations(Request $request)
    {
        $Response = $this->Income_CreateMassive(
            $request,
        );

        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }

    public function download_template(Request $request){
        return Storage::disk('incomes')->download('income_template.xlsx');
    }

    //Get incomes page
    public function get_page(Request $request){
        $Response = $this->Income_GetPage(
            $request->pagination,
            $request->search,
            $request->state,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get income page by client id
    public function get_page_by_client_id(Request $request){
        $Response = $this->Income_GetPageByClientId(
            Session::get('client_user')['active_client']['id'],
            $request->pagination,
            $request->search,
            $request->state,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get income licenses
    public function get_licenses(Request $request){
        $Response = $this->Income_GetIncomeLicenses(
            $request->income_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Update Income
    public function update_income(Request $request){
        $Response = $this->Income_UpdateIncome(
            $request->id,
            $request->state,
            $request->client_id,
            $request->client_identification,
            $request->client_name,
            $request->timely_payment,
            $request->cutoff_date,
            $request->description,
            $request->bill_name,
            $request->bill_final_value,
            $request->licenses
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Change income state
    public function change_state(Request $request){
        $Response = $this->Income_UpdateIncomeState(
            $request->income_id,
            $request->state
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Change income state to paid
    public function change_state_to_pay(Request $request){
        $Response = $this->Income_UpdateIncomePaymentData(
            $request->income_id
            ,1
            ,Carbon::now()
            ,'Pago manual realizado por el administrador '.Session::get('user')['email']
            ,$request->bill_name
            ,$request->bill_final_value
            ,filter_var($request->notify_client, FILTER_VALIDATE_BOOLEAN)
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Send income
    public function send_income(Request $request){
        $Response = $this->Income_SendIncome(
            $request->income_id
            ,$request->receivers
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get income data for unlogged user
    public function get_income_data_for_payment_unlogged(Request $request){
        $Response = $this->Income_GetIncomeDataForPaymentUnlogged(
            $request->unique_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_incomes_by_state_date_range_report(Request $request){
        $Response = $this->Income_GetIncomesByStateDateRangeReport(
            $request->fromDate
            ,$request->toDate
            ,$request->states
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_incomes_payed_by_date_range_report(Request $request){
        $Response = $this->Income_GetIncomesPayedByDateRangeReport(
            $request->fromDate
            ,$request->toDate
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }

    public function create_siigo_invoice(Request $request) {
        $income = income::find($request->income_id);
        if ($income == null) {
            return \Response::json([
                'status' => 0,
                'message' => 'Income not found'
            ], 400);
        }

        $Response = $this->Income_CreateSiigoInvoice($income);
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
