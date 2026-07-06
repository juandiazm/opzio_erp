<?php 
namespace App\traits;

use App\Models\department;
use App\Models\client_document;
use App\Models\employee;

use Illuminate\Support\Str;


trait departments_trait
{
    //Add Department
    public function Department_Addepartment(
        $name,
        $budget,
        $director_id
    ){
        try{
            $department = department::where('name', $name)->first();
            if($department){
                return [
                    'status' => 0,
                    'message' => 'El departamento ya existe'
                ];
            }
            $this->Department_RemoveDirectorForAnotherDepartment($director_id);
            $department = new department();
            $department->unique_id = strtoupper(Str::uuid()->toString());
            $department->name = $name;
            $department->budget = $budget;
            $department->director_id = $director_id;
            $department->save();
            //get employee by id
            $employee = employee::where('id', $director_id)->first();
            if($employee){
                $employee->department_id = $department->id;
                $employee->save();
            }
            $department->director = $employee;
            $department->employees = [$employee];
            return [
                'status' => 1,
                'message' => 'Departamento agregado',
                'department' => $department
            ];
        }catch(\Exception $e){
            info('Department_Addepartment error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Remove director for another department
    public function Department_RemoveDirectorForAnotherDepartment(
        $director_id
    ){
        try{
            $employee = employee::where('id', $director_id)->get();
            foreach($employee as $e){
                $e->department_id = null;
                $e->save();
            }
            $department = department::where('director_id', $director_id)->get();
            foreach($department as $d){
                $d->director_id = null;
                $d->save();
            }
        }catch(\Exception $e){
            info('Department_RemoveDirectorForAnotherDepartment error: '.$e->getMessage());
        }
    }
    //Update Department
    public function Department_UpdateDepartment(
        $id,
        $name,
        $budget,
        $director_id
    ){
        try{
            $this->Department_RemoveDirectorForAnotherDepartment($director_id);
            $department = department::where('id', $id)->first();
            if(!$department){
                return [
                    'status' => 0,
                    'message' => 'El departamento no existe'
                ];
            }
            $department->name = $name;
            $department->budget = $budget;
            //get employee by id
            $employee = employee::where('id', $director_id)->first();
            if($employee){
                $department->director_id = $employee->id;
                $employee->department_id = $department->id;
                $employee->save();
            }
            $department->save();
            $department->director = $employee;
            return [
                'status' => 1,
                'message' => 'Departamento actualizado',
                'department' => $department
            ];
        }catch(\Exception $e){
            info('Department_UpdateDepartment error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get Department page
    public function Department_GetPage(
        $pagination,
        $search,
        $with_trashed = false
    ){
        try{
            $departments = department::orderBy('name')->where('name', 'like', '%'.$search.'%')
            ->orWhere('budget', 'like', '%'.$search.'%');
            if($with_trashed){
                $departments = $departments->withTrashed();
            }
            if($search != null && $search != ''){
                $departments = $departments->where('name', 'like', '%'.$search.'%')
                ->orWhere('unique_id', 'like', '%'.$search.'%')
                ;
            }
            $pagination['total'] = $departments->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $departments = $departments->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            $directors = employee::whereIn('id', $departments->pluck('director_id'))->get();
            $employees = employee::whereIn('department_id', $departments->pluck('id'))->get();
            foreach($departments as $department){
                $department->director = $directors->where('id', $department->director_id)->first();
                $department->employees = $employees->where('department_id', $department->id)->values()->all();
            }
            return [
                'status' => 1,
                'departments' => $departments,
                'pagination' => $pagination
            ];
        }catch(\Exception $e){
            info('Department_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get all departments
    public function Department_GetAll(){
        try{
            $departments = department::orderBy('name')->get();
            return [
                'status' => 1,
                'departments' => $departments
            ];
        }catch(\Exception $e){
            info('Department_GetAll error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Delete Department
    public function Department_DeleteDepartment(
        $id
    ){
        try{
            $department = department::where('id', $id)->first();
            if(!$department){
                return [
                    'status' => 0,
                    'message' => 'El departamento no existe'
                ];
            }
            $department->delete();
            return [
                'status' => 1,
                'message' => 'Departamento eliminado',
                'data' => $department
            ];
        }catch(\Exception $e){
            info('Department_DeleteDepartment error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Restore Department
    public function Department_RestoreDepartment(
        $id
    ){
        try{
            $department = department::where('id', $id)->withTrashed()->first();
            if(!$department){
                return [
                    'status' => 0,
                    'message' => 'El departamento no existe'
                ];
            }
            $department->restore();
            return [
                'status' => 1,
                'message' => 'Departamento restaurado'
            ];
        }catch(\Exception $e){
            info('Department_RestoreDepartment error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}