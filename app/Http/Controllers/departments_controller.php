<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\departments_trait;

class departments_controller extends Controller
{
    use departments_trait;
    //Add Department
    public function add_department(Request $request){
        $Response = $this->Department_Addepartment(
            $request->name,
            $request->budget,
            $request->director_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Update Department
    public function update_department(Request $request){
        $Response = $this->Department_UpdateDepartment(
            $request->id,
            $request->name,
            $request->budget,
            $request->director_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Get Department page
    public function get_page(Request $request){
        $Response = $this->Department_GetPage(
            $request->pagination,
            $request->search,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //get all departments
    public function get_all(){
        $Response = $this->Department_GetAll();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Delete Department
    public function delete_department(Request $request){
        $Response = $this->Department_DeleteDepartment(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //Restore Department
    public function restore_department(Request $request){
        $Response = $this->Department_RestoreDepartment(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
