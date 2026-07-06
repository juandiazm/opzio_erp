<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\employees_trait;

class employees_controller extends Controller
{
    //
    use employees_trait;
    public function add_employee(Request $request){
        $Response = $this->Employee_AddEmployee(
            $request->name,
            $request->last_name,
            $request->id_type,
            $request->identification,
            $request->country,
            $request->phone,
            $request->personal_email,
            $request->work_email,
            $request->state,
            $request->photo
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_employee(Request $request){
        $Response = $this->Employee_UpdateEmployee(
            $request->id,
            $request->name,
            $request->last_name,
            $request->id_type,
            $request->identification,
            $request->country,
            $request->phone,
            $request->personal_email,
            $request->work_email,
            $request->state,
            $request->photo
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_employee(Request $request){
        $Response = $this->Employee_DeleteEmployee(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400); 
    }
    public function restore_employee(Request $request){
        $Response = $this->Employee_RestoreEmployee(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400); 
    }
    public function get_employee_by_id(Request $request){
        $Response = $this->Employee_GetEmployeeById(
            $request->employee_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_page(Request $request){
        $Response = $this->Employee_GetPage(
            $request->pagination,
            $request->search,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_all(Request $request){
        $Response = $this->Employee_GetAll();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400); 
    }
    public function update_employee_hiring(Request $request){
        $Response = $this->Employee_UpdateEmployeeHiring(
            $request->id,
            $request->entry_date,
            $request->payment_type,
            $request->bank,
            $request->account_number,
            $request->account_type,
            $request->salary,
            $request->contract,
            $request->department_id,
            $request->charge,
            $request->eps_id,
            $request->afp_id,
            $request->arl_id,
            $request->retirement_date
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_employee_document(Request $request){
        $Response = $this->Employee_AddEmployeeDocument(
            $request->employee_id,
            $request->name,
            $request->file
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_employee_documents(Request $request){
        $Response = $this->Employee_GetEmployeeDocuments(
            $request->employee_id,
            $request->search
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_employee_document(Request $request){
        $Response = $this->Employee_UpdateEmployeeDocument(
            $request->id,
            $request->name
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_employee_document(Request $request){
        $Response = $this->Employee_DeleteEmployeeDocument(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_not_deparment_assigned(Request $request){
        $Response = $this->Employee_GetNotDeparmentAssigned();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400); 
    }
    public function get_employees_by_date_range_report(Request $request){
        $Response = $this->Employee_GetEmployeesByDateRangeReport(
            $request->fromDate
            ,$request->toDate
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
