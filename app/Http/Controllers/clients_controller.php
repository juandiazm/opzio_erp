<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

use App\traits\clients_trait;

class clients_controller extends Controller
{
    //
    use clients_trait;
    public function add_client(Request $request){
        $Response = $this->Client_AddClient(
            $request->verified
            ,$request->state
            ,$request->name
            ,$request->lastname
            ,$request->email
            ,$request->identification_type
            ,$request->identification
            ,$request->country
            ,$request->address
            ,$request->phone
            ,$request->sector
            ,$request->description
            ,$request->photo
            ,$request->value_per_hour
            ,$request->electronic_invoice
            , false
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_client(Request $request){
        $Response = $this->Client_UpdateClient(
            $request->id
            ,$request->verified
            ,$request->state
            ,$request->name
            ,$request->lastname
            ,$request->email
            ,$request->identification_type
            ,$request->identification
            ,$request->country
            ,$request->address
            ,$request->phone
            ,$request->sector
            ,$request->value_per_hour
            ,$request->description
            ,$request->photo
            ,$request->electronic_invoice
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function my_company_update_client(Request $request){
        $Response = $this->Client_MyCompanyUpdateClient(
            Session::get('client_user')['active_client']['id']
            ,$request->email
            ,$request->identification_type
            ,$request->country
            ,$request->address
            ,$request->phone
            ,$request->sector
            ,$request->photo
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_client_by_id(Request $request){
        $Response = $this->Client_GetClientById(
            $request->client_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_page(Request $request){
        $Response = $this->Client_GetPage(
            $request->pagination,
            $request->search
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_client_document(Request $request){
        $Response = $this->Client_AddClientDocument(
            $request->client_id,
            $request->name,
            $request->file
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_client_documents(Request $request){
        $Response = $this->Client_GetClientDocuments(
            $request->client_id,
            $request->search
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_client_document(Request $request){
        $Response = $this->Client_UpdateClientDocument(
            $request->id,
            $request->name
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_client_document(Request $request){
        $Response = $this->Client_DeleteClientDocument(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_all(Request $request){
        $Response = $this->Client_GetAll(
            $request->search
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function register_client(Request $request){
        $Response = $this->Client_RegisterClient(
            $request->name
            ,$request->identification_type
            ,$request->identification
            ,$request->email
            ,$request->country_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_clients_by_date_range_report(Request $request){
        $Response = $this->Client_GetClientsByDateRangeReport(
            $request->fromDate
            ,$request->toDate
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function sincronize_with_siigo()
    {
        $response = $this->Client_SyncWithSiigo();
        
        if ($response['status'] == 1) {
            return $response;
        }
        
        return \Response::json($response, 400);
    }
}
