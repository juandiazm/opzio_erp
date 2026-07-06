<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

use App\traits\licenses_trait;

class licenses_controller extends Controller
{
    use licenses_trait;
    //Add License
    public function add_license(Request $request){
        $Response = $this->License_Addlicense(
            $request->state,
            $request->client_id,
            $request->name,
            $request->service_id,
            $request->employee_id,
            $request->value,
            $request->description,
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Update License
    public function update_license(Request $request){
        $Response = $this->License_UpdateLicense(
            $request->id,
            $request->state,
            $request->client_id,
            $request->name,
            $request->service_id,
            $request->employee_id,
            $request->value,
            $request->description,
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Update Licenses Comission
    public function update_license_comission(Request $request){
        $Response = $this->License_UpdateLicenseComission(
            $request->id,
            $request->comission
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Update License Details
    public function update_license_details(Request $request){
        $Response = $this->License_UpdateLicenseDetails(
            $request->id,
            $request->type,
            $request->recurrence_months,
            $request->billing_day,
            $request->days_to_expire,
            $request->next_billing_date,
            $request->last_payed_date
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get License page
    public function get_page(Request $request){
        $Response = $this->License_GetPage(
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
    //Get License page by client id
    public function get_page_by_client_id(Request $request){
        $Response = $this->License_GetPageByClientId(
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
    //get all licenses
    public function get_all(){
        $Response = $this->License_GetAll();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get license by id
    public function get_license_by_id(Request $request){
        $Response = $this->License_GetLicenseById(
            $request->license_id,
            $request->search,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //get license by employee id
    public function get_license_by_employee_id(Request $request){
        $Response = $this->License_GetLicenseByEmployeeId(
            $request->employee_id,
            $request->search,
            false
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //get license by client id
    public function get_license_by_client_id(Request $request){
        $Response = $this->License_GetLicenseByClientId(
            $request->client_id,
            $request->search,
            false
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //Delete License
    public function delete_license(Request $request){
        $Response = $this->License_DeleteLicense(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Restore License
    public function restore_license(Request $request){
        $Response = $this->License_RestoreLicense(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    // Get by user key
    public function get_license_by_user_key(Request $request){
        $Response = $this->License_GetLicenseByUserKey(
            $request->user_key,
            $request->password_key,
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function get_license_by_user_keys(Request $request){
        $Response = $this->License_GetLicenseByUserKeys(
            $request->credentials
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function add_license_document(Request $request){
        $Response = $this->License_AddLicenseDocument(
            $request->license_id,
            $request->name,
            $request->file
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_license_documents(Request $request){
        $Response = $this->License_GetLicenseDocuments(
            $request->license_id,
            $request->search
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_license_document(Request $request){
        $Response = $this->License_UpdateLicenseDocument(
            $request->id,
            $request->name
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_license_document(Request $request){
        $Response = $this->License_DeleteLicenseDocument(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_license_notification(Request $request){
        $Response = $this->License_AddLicenseNotification(
            $request->license_id,
            $request->email,
            $request->phone,
            $request->state
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function get_license_notifications(Request $request){
        $Response = $this->License_GetLicenseNotifications(
            $request->license_id,
            $request->search,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function get_license_notifications_by_license_ids(Request $request){
        $Response = $this->License_GetLicenseNotificationsByLicensesIds(
            $request->license_ids
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function update_license_notification(Request $request){
        $Response = $this->License_UpdateLicenseNotification(
            $request->id,
            $request->email,
            $request->phone,
            $request->state
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function delete_license_notification(Request $request){
        $Response = $this->License_DeleteLicenseNotification(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function restore_license_notification(Request $request){
        $Response = $this->License_RestoreLicenseNotification(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function change_license_notification_position(Request $request){
        $Response = $this->License_ChangeLicenseNotificationPosition(
            $request->id,
            $request->direction
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    public function get_licenses_by_date_range_report(Request $request){
        $Response = $this->License_GetLicensesByDateRangeReport(
            $request->fromDate
            ,$request->toDate
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
