<?php 
namespace App\traits;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

use App\Models\employee;
use App\Models\employee_document;

use App\Models\afp;
use App\Models\arl;
use App\Models\eps;
use App\Models\country;

use App\Models\department;

use Session;


trait employees_trait
{
    private $URL_EMPLOYEES_PATH = 'images/erp/employees/';
    public function Employee_AddEmployee(
        $name,
        $last_name,
        $id_type,
        $identification,
        $country,
        $phone,
        $personal_email,
        $work_email,
        $state,
        $photo
    ){
        try{
            $employee = employee::where('identification', $identification)->first();
            if($employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado ya existe'
                ];
            }
            $employee = new employee();
            $employee->uid = strtoupper(Str::uuid()->toString());
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $employee->photo = strtoupper(Str::uuid()->toString()).'.webp';
                $photo->save($this->URL_EMPLOYEES_PATH . $employee->photo);
                ImageOptimizer::optimize($this->URL_EMPLOYEES_PATH . $employee->photo);
            }
            $employee->name = $name;
            $employee->last_name = $last_name;
            $employee->id_type = $id_type;
            $employee->identification = $identification;
            $employee->country = $country;
            $employee->phone = $phone;
            $employee->personal_email = $personal_email;
            $employee->work_email = $work_email;
            $employee->state = $state;
            $employee->save();
            //get country data
            $employee->country = country::where('id', $employee->country)->first();
            return [
                'status' => 1,
                'message' => 'Empleado agregado',
                'employee' => $employee
            ];
        }catch(\Exception $e){
            info('Employee_AddEmployee error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_UpdateEmployee(
        $id,
        $name,
        $last_name,
        $id_type,
        $identification,
        $country,
        $phone,
        $personal_email,
        $work_email,
        $state,
        $photo
    ){
        try{
            $employee = employee::where('id', $id)->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado no existe'
                ];
            }
            $employee->name = $name;
            $employee->last_name = $last_name;
            $employee->id_type = $id_type;
            $employee->identification = $identification;
            $employee->country = $country;
            $employee->phone = $phone;
            $employee->personal_email = $personal_email;
            $employee->work_email = $work_email;
            $employee->state = $state;
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $photo_uid = strtoupper(Str::uuid()->toString()).'.webp';
                $photo->save($this->URL_EMPLOYEES_PATH . $photo_uid);
                ImageOptimizer::optimize($this->URL_EMPLOYEES_PATH . $photo_uid);
                //Delete image
                if($employee->photo != null){
                    File::delete(public_path($this->URL_EMPLOYEES_PATH . $employee->photo));
                }
                $employee->photo = $photo_uid;
            }
            $employee->save();
            return [
                'status' => 1,
                'message' => 'Empleado actualizado'
            ];
        }catch(\Exception $e){
            info('Employee_UpdateEmployee error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_DeleteEmployee(
        $id
    ){
        try{
            $employee = employee::where('id', $id)->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado no existe'
                ];
            }
            $employee->delete();
            return [
                'status' => 1,
                'message' => 'Empleado eliminado',
                'data' => $employee
            ];
        }catch(\Exception $e){
            info('Employee_DeleteEmployee error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Employee_RestoreEmployee(
        $id
    ){
        try{
            $employee = employee::where('id', $id)->withTrashed()->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado no existe'
                ];
            }
            $employee->restore();
            return [
                'status' => 1,
                'message' => 'Empleado restaurado',
                'data' => $employee
            ];
        }catch(\Exception $e){
            info('Employee_RestoreEmployee error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Employee_GetEmployeeById(
        $id
    ){
        try{
            $employee = employee::where('id', $id)->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleade no existe'
                ];
            }
            //get country data
            $employee->country = country::where('id', $employee->country)->first();
            return [
                'status' => 1,
                'message' => 'empleade obtenido',
                'data' => $employee
            ];
        }catch(\Exception $e){
            info('Employee_GetEmployeeById error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_LoginEmployee(
        $identification
        ,$password
    ){
        try{
            $employee = employee::where('email', $identification)->orWhere('identification', $identification)->orWhere('employeename', $identification)->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado no existe'
                ];
            }
            if(!Hash::check($password, $employee->password)){
                return [
                    'status' => 0,
                    'message' => 'La contraseña es incorrecta'
                ];
            }
            Session::put('employee', $employee);
            return [
                'status' => 1,
                'message' => 'empleado logueado'
            ];
        }catch(\Exception $e){
            info('Employee_LoginEmployee error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_GetPage(
        $pagination
        ,$search
        ,$with_trashed = false
    ){
        try{
            $employees = employee::orderBy('name');
            if($with_trashed){
                $employees = $employees->withTrashed();
            }
            if($search != null && $search != ''){
                $employees = $employees->where('name', 'like', '%'.$search.'%')
                ->orWhere('last_name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('personal_email', 'like', '%'.$search.'%')
                ->orWhere('work_email', 'like', '%'.$search.'%')
                ->orWhere('uid', 'like', '%'.$search.'%')
                ->orWhere('identification', 'like', '%'.$search.'%');
            }
            $pagination['total'] = $employees->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $employees = $employees->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            //assoc data
            $epss = eps::whereIn('id', $employees->pluck('eps_id'))->get();
            $afps = afp::whereIn('id', $employees->pluck('afp_id'))->get();
            $arls = arl::whereIn('id', $employees->pluck('arl_id'))->get();
            $countries = country::whereIn('id', $employees->pluck('country'))->get();
            $departments = department::whereIn('id', $employees->pluck('department_id'))->get();
            foreach($employees as $employee){
                $employee->eps = $epss->firstWhere('id', $employee->eps_id);
                $employee->afp = $afps->firstWhere('id', $employee->afp_id);
                $employee->arl = $arls->firstWhere('id', $employee->arl_id);
                $employee->country = $countries->firstWhere('id', $employee->country);
                $employee->department = $departments->firstWhere('id', $employee->department_id);
            }
            return [
                'status' => 1,
                'message' => 'Empleados obtenidos',
                'pagination' => $pagination,
                'data' => $employees
            ];
        }catch(\Exception $e){
            info('Employee_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_UpdateEmployeeHiring(
        $id,
        $entry_date,
        $payment_type,
        $bank,
        $account_number,
        $account_type,
        $salary,
        $contract,
        $department_id,
        $charge,
        $eps_id,
        $afp_id,
        $arl_id,
        $retirement_date
    ){
        try{
            $employee = employee::where('id', $id)->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado no existe'
                ];
            }
            $employee->entry_date = $entry_date;
            $employee->payment_type = $payment_type;
            $employee->bank = $bank;
            $employee->account_number = $account_number;
            $employee->account_type = $account_type;
            $employee->salary = $salary;
            $employee->contract = $contract;
            $employee->department_id = $department_id;
            $employee->charge = $charge;
            $employee->eps_id = $eps_id;
            $employee->afp_id = $afp_id;
            $employee->arl_id = $arl_id;
            $employee->retirement_date = $retirement_date;
            $employee->save();
            return [
                'status' => 1,
                'message' => 'Contrato actualizado'
            ];
        }catch(\Exception $e){
            info('Employee_UpdateEmployeeHiring error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Employee_CloseSession(){
        try{
            Session::forget('employee');
            return [
                'status' => 1,
                'message' => 'Sesión cerrada'
            ];
        }catch(\Exception $e){
            info('Employee_CloseSession error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Documents
    public function Employee_AddEmployeeDocument(
        $employee_id
        ,$name
        ,$file
    ){
        try{
            $employee = employee::where('id', $employee_id)->first();
            if(!$employee){
                return [
                    'status' => 0,
                    'message' => 'El empleado no existe'
                ];
            }
            $accepted_format = ['pdf','docx','xlsx','pptx'];
            $file_format = strtolower($file->getClientOriginalExtension());
            if(($accepted_format == null || in_array($file_format, $accepted_format))){
                $uid = strtoupper(Str::uuid()->toString()).'.'.$file_format;
                Storage::disk('employee_document')->put($uid, file_get_contents($file));
                $document = new employee_document();
                $document->employee_id = $employee_id;
                $document->document_public_name = $name;
                $document->document_private_name = $uid;
                $document->save();
                return [
                    'status' => 1,
                    'message' => 'Documento agregado',
                    'id' => $document->id
                ];
            }
            return [
                'status' => 0,
                'message' => 'Formato de archivo no aceptado'
            ];
        }catch(\Exception $e){
            info('Employee_AddEmployeeDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_GetEmployeeDocuments(
        $employee_id
        ,$search
    ){
        try{
            $documents = employee_document::where('employee_id', $employee_id)->orderBy('document_public_name');
            if($search != null && $search != ''){
                $documents = $documents->where('document_public_name', 'like', '%'.$search.'%');
            }
            $documents = $documents->get();
            foreach($documents as $document){
                $document->document_url = Storage::disk('employee_document')->url($document->document_private_name);
            }
            return [
                'status' => 1,
                'message' => 'Documentos obtenidos',
                'data' => $documents
            ];
        }catch(\Exception $e){
            info('Employee_GetEmployeeDocuments error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Employee_UpdateEmployeeDocument(
        $id
        ,$name
    ){
        try{
            $document = employee_document::where('id', $id)->first();
            if(!$document){
                return [
                    'status' => 0,
                    'message' => 'El documento no existe'
                ];
            }
            $document->document_public_name = $name;
            $document->save();
            return [
                'status' => 1,
                'message' => 'Documento actualizado'
            ];
        }catch(\Exception $e){
            info('Employee_UpdateEmployeeDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Employee_DeleteEmployeeDocument(
        $id
    ){
        try{
            $document = employee_document::where('id', $id)->first();
            if(!$document){
                return [
                    'status' => 0,
                    'message' => 'El documento no existe'
                ];
            }
            Storage::disk('employee_document')->delete($document->document_private_name);
            $document->delete();
            return [
                'status' => 1,
                'message' => 'Documento eliminado'
            ];
        }catch(\Exception $e){
            info('Employee_DeleteEmployeeDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Employee_GetAll(){
        try{
            $employees = employee::orderBy('name')->get();
            return [
                'status' => 1,
                'message' => 'Empleados obtenidos',
                'data' => $employees
            ];
        }catch(\Exception $e){
            info('Employee_GetAll error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Employee_GetEmployeesByDateRangeReport(
        $date_from
        ,$date_to
    ){
        $Reponse = [
            'status' => 0,
            'message' => 'No se encontraron usuarios',
            'data' => []
        ];
        try{
            $date_from = Carbon::parse($date_from);
            $date_to = Carbon::parse($date_to);
            $Reponse = $this->Employee_GetEmployeesByDateRange($date_from, $date_to);
            if($Reponse['status'] == 0){
                return $Reponse;
            }
            $employees = $Reponse['data'];
            $date_diff = $date_to->diffInDays($date_from);
            if($date_diff < 31){
                $report = $employees->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('d M Y');
                })->map(function($grupped_employees) {
                    // Return the count of employees per day
                    return [
                        'label' => $grupped_employees->first()->created_at->format('d M Y'). ' - '.$grupped_employees->count(),
                        'total' => $grupped_employees->count()
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
                $report = $all_days->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }else if($date_diff<365){
                //sum employee by month
                $report = $employees->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('M Y');
                })->map(function($grupped_employees) {
                    // Return the count of employees per month
                    return [
                        'label' => $grupped_employees->first()->created_at->format('M Y'). ' - '.$grupped_employees->count(),
                        'total' => $grupped_employees->count()
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
                $report = $all_months->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }else{
                //sum employee by year
                $report = $employees->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('Y');
                })->map(function($grupped_employees) {
                    // Return the count of employees per year
                    return [
                        'label' => $grupped_employees->first()->created_at->format('Y'). ' - '.$grupped_employees->count(),
                        'total' => $grupped_employees->count()
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
                $report = $all_years->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }
            Storage::disk('reports')->put('employees'.Session::get('user')['unique_id'].'.json', json_encode($employees));
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de empleados obtenido',
                'data' => [
                    'employees' => $employees,
                    'report' => $report
                ]
            ];
        }catch(\Exception $e){
            info('Employee_GetEmployeesByDateRangeReport error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function Employee_GetEmployeesByDateRange(
        $date_from
        ,$date_to
    ){
        try{
            $employees = employee::
            with(['eps', 'afp', 'arl', 'country'])
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->orderBy('created_at', 'asc')
            ->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $employees
            ];
        }catch(\Exception $e){
            info('Employee_GetEmployeesByDateRange error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}