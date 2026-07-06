<?php 
namespace App\traits;

use Illuminate\Support\Facades\Storage;
use Session;


use App\Models\license;
use App\Models\client;
use App\Models\service;
use App\Models\employee;
use App\Models\tax;
use App\Models\income;

use App\Models\license_document;
use App\Models\license_notification;

use Carbon\Carbon;


use Illuminate\Support\Str;


trait licenses_trait
{
    use
    twilio_sms_trait
    ;
    //Set License web data
    public function License_WebData(
        $license
    ){
        //get client by id
        $client = client::where('id', $license->client_id)->first();
        if($client){
            $license->client = $client;
        }
        //get service by id
        $service = service::where('id', $license->service_id)->first();
        if($service){
            $license->service = $service;
            if($license['service']['tax_id'] != null){
                $license['service']['tax'] = tax::where('id', $license['service']['tax_id'])->first();
            }
        }
        //get employee by id
        $employee = employee::where('id', $license->employee_id)->first();
        if($employee){
            $license->employee = $employee;
        }
        //set type string
        switch($license->type){
            case 1:
                $license->type_string = 'Recurrente';
                break;
            case 2:
                $license->type_string = 'Estatica';
                break;
        }
        //
        if($license->last_billing_date) $license->last_billing_date = Carbon::parse($license->last_billing_date)->format('Y-m-d');
        if($license->next_billing_date) $license->next_billing_date = Carbon::parse($license->next_billing_date)->format('Y-m-d');
        if($license->last_payed_date)   $license->last_payed_date = Carbon::parse($license->last_payed_date)->format('Y-m-d');
        return $license;
    }
    //Add License
    public function License_Addlicense(
        $active,
        $client_id,
        $name,
        $service_id,
        $employee_id,
        $value,
        $description,
        $id = null
    ){
        try{
            $license = license::where('name', $name)->first();
            if($license){
                return [
                    'status' => 0,
                    'message' => 'La licencia ya existe'
                ];
            }
            $license = new license();
            if($id != null) $license->id = $id;
            $license->unique_id = strtoupper(Str::uuid()->toString());
            $license->user_key = strtoupper(Str::uuid()->toString());
            $license->password_key = strtoupper(Str::uuid()->toString());
            $license->active = $active;
            $license->client_id = $client_id;
            $license->name = $name;
            $license->service_id = $service_id;
            $license->employee_id = $employee_id;
            $license->value = $value;
            $license->description = $description;
            $license->billing_day = Carbon::now()->day+1;
            $license->type = 2;
            $license->save();
            $license = $license->refresh();
            //add default notification
            $client = client::where('id', $client_id)->first();
            $license_notification = new license_notification();
            $license_notification->license_id = $license->id;
            $license_notification->email = $client->email;
            $license_notification->phone = $client->phone;
            $license_notification->active = 1;
            $license_notification->save();
            $license = $this->License_WebData($license);
            return [
                'status' => 1,
                'message' => 'Licencia agregada',
                'license' => $license
            ];
        }catch(\Exception $e){
            info('License_Adlicense error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Update License
    public function License_UpdateLicense(
        $id,
        $active,
        $client_id,
        $name,
        $service_id,
        $employee_id,
        $value,
        $description
    ){
        try{
            $license = license::where('id', $id)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license->active = $active;
            $license->client_id = $client_id;
            $license->name = $name;
            $license->service_id = $service_id;
            $license->employee_id = $employee_id;
            $license->value = $value;
            $license->description = $description;
            $license->save();
            $license = $this->License_WebData($license);
            return [
                'status' => 1,
                'message' => 'Licencia actualizada',
                'license' => $license
            ];
        }catch(\Exception $e){
            info('License_UpdateLicense error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Update License Comission
    public function License_UpdateLicenseComission(
        $id,
        $comission
    ){
        try{
            $license = license::where('id', $id)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license->comission = $comission;
            $license->save();
            $license = $this->License_WebData($license);
            return [
                'status' => 1,
                'message' => 'Comision de licencia actualizada',
                'license' => $license
            ];
        }catch(\Exception $e){
            info('License_UpdateLicenseComission error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Licese_CalculateRemainingDays($last_payed_date, $recurrence_months, $next_billing_date){
        $remaining_days = 0;
        if($last_payed_date == null){
            $remaining_days = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($next_billing_date)->startOfDay(), false);
        }else{
            $remaining_days = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($last_payed_date)->addMonths($recurrence_months)->startOfDay(), false);
        }
        return $remaining_days;
    }
    //update License Details
    public function License_UpdateLicenseDetails(
        $id,
        $type,
        $recurrence_months,
        $billing_day,
        $days_to_expire,
        $next_billing_date,
        $last_payed_date
    ){
        try{
            $license = license::where('id', $id)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license->type = $type;
            $license->recurrence_months = $recurrence_months;
            $license->billing_day = $billing_day;
            $license->days_to_expire = $days_to_expire;
            $license->next_billing_date = $next_billing_date;
            $license->remaining_days =  $this->Licese_CalculateRemainingDays($last_payed_date, $recurrence_months, $next_billing_date);
            $license->last_payed_date = $last_payed_date;
            $license->save();
            $license = $this->License_WebData($license);
            return [
                'status' => 1,
                'message' => 'Detalles de licencia actualizados',
                'license' => $license
            ];
        }catch(\Exception $e){
            info('License_UpdateLicenseDetails error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get License page
    public function License_GetPage(
        $pagination,
        $search,
        $state = null,
        $with_trashed = false
    ){
        try{
            $licenses = license::orderBy('client_id');
            if($search != null && $search != ''){
                //search by client name (client['name']'s license name'
                $clients_ids = client::where(function($query) use ($search){
                    $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('unique_id', 'like', '%'.$search.'%')
                    ;
                })->pluck('id');
            }
            if($with_trashed){
                $licenses = $licenses->withTrashed();
            }
            if($state != null && $state != '') $licenses = $licenses->where('active', $state);
            if($search != null && $search != ''){
                $licenses = $licenses->where(function($query) use ($search, $clients_ids){
                    $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('value', 'like', '%'.$search.'%')
                    ->orWhere('unique_id', 'like', '%'.$search.'%')
                    ->orWhereIn('client_id', $clients_ids)
                    ;
                });
            }
            $pagination['total'] = $licenses->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $licenses = $licenses->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            foreach($licenses as $license){
                $license = $this->License_WebData($license);
            }
            //order by license['client']['name']
            $licenses = collect($licenses)->sortBy(function($license){
                return $license['client']['name'];
            })->values()->all();
            return [
                'status' => 1,
                'licenses' => $licenses,
                'pagination' => $pagination
            ];
        }catch(\Exception $e){
            info('License_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get License page by client id
    public function License_GetPageByClientId(
        $client_id,
        $pagination,
        $search,
        $state = null,
        $with_trashed = false
    ){
        try{
            $licenses = license::where('client_id', $client_id)->orderBy('name')->where('name', 'like', '%'.$search.'%');
            if($state != null && $state != '') $licenses = $licenses->where('active', $state);
            if($with_trashed){
                $licenses = $licenses->withTrashed();
            }
            if($search != null && $search != ''){
                $licenses = $licenses->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('value', 'like', '%'.$search.'%')
                ->orWhere('unique_id', 'like', '%'.$search.'%')
                ;
            }
            $pagination['total'] = $licenses->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $licenses = $licenses->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            foreach($licenses as $license){
                $license = $this->License_WebData($license);
            }
            return [
                'status' => 1,
                'licenses' => $licenses,
                'pagination' => $pagination
            ];
        }catch(\Exception $e){
            info('License_GetPageByClientId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get all licenses
    public function License_GetAll(){
        try{
            $licenses = license::orderBy('name')->get();
            return [
                'status' => 1,
                'licenses' => $licenses
            ];
        }catch(\Exception $e){
            info('License_GetAll error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get license by id
    public function License_GetLicenseById(
        $id,
        $search,
        $with_trashed = false
    ){
        try{
            $license = license::where('id', $id)->where('name', 'like', '%'.$search.'%');
            if($with_trashed){
                $license = $license->withTrashed();
            }
            $license = $license->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license = $this->License_WebData($license);
            return [
                'status' => 1,
                'license' => $license
            ];
        }catch(\Exception $e){
            info('License_GetLicenseById error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Get license by employee id
    public function License_GetLicenseByEmployeeId(
        $employee_id,
        $search,
        $with_trashed = false
    ){
        try{
            $licenses = license::where('employee_id', $employee_id)->orderBy('name')->where('name', 'like', '%'.$search.'%');
            if($with_trashed){
                $licenses = $licenses->withTrashed();
            }
            $licenses = $licenses->get();
            foreach($licenses as $license){
                $license = $this->License_WebData($license);
            }
            return [
                'status' => 1,
                'licenses' => $licenses
            ];
        }catch(\Exception $e){
            info('License_GetLicenseByEmployeeId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Get license by client id
    public function License_GetLicenseByClientId(
        $client_id,
        $search,
        $with_trashed = false
    ){
        try{
            $licenses = license::where('client_id', $client_id)->orderBy('name')->where('name', 'like', '%'.$search.'%');
            if($with_trashed){
                $licenses = $licenses->withTrashed();
            }
            $licenses = $licenses->get();
            foreach($licenses as $license){
                $license = $this->License_WebData($license);
            }
            return [
                'status' => 1,
                'licenses' => $licenses
            ];
        }catch(\Exception $e){
            info('License_GetLicenseByClientId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Delete License
    public function License_DeleteLicense(
        $id
    ){
        try{
            $license = license::where('id', $id)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => ';a licencia no existe'
                ];
            }
            $license->delete();
            return [
                'status' => 1,
                'message' => 'Licencia eliminada',
                'data' => $license
            ];
        }catch(\Exception $e){
            info('License_DeleteLicense error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Restore License
    public function License_RestoreLicense(
        $id
    ){
        try{
            $license = license::where('id', $id)->withTrashed()->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license->restore();
            return [
                'status' => 1,
                'message' => 'Licencia restaurado'
            ];
        }catch(\Exception $e){
            info('License_RestoreLicense error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_GetLicenseByUserKey(
        $user_key,
        $password_key,
        $id = null
    ){
        try{
            $license = license::
            select(['id', 'unique_id', 'active', 'name', 'value', 'days_to_expire', 'next_billing_date', 'remaining_days', 'client_id', 'recurrence_months','user_key', 'password_key'])
            ->with(['income_licenses' => function($query){
                $query->select(['id', 'license_id', 'income_id', 'timely_payment', 'license_name', 'service_name', 'recurrence_months']);
                $query->whereHas('income', function($query){
                    $query->whereIn('state', [0, 2]);
                })
                ->with('income', function($query){
                    $query->select(['id', 'unique_id', 'timely_payment', 'cutoff_date', 'total']);
                });
            }])
            ->with(['client' => function($query){
                $query->select(['id', 'name', 'photo', 'identification']);
            }])
            ->where('active', 1)
            ->where('user_key', $user_key)
            ->where('password_key', $password_key)
            ->first();
            if(!$license){
                if($id != null && $id == $user_key){
                    $license = license::where('id', $id)->first();
                    if($license){
                        $Response = $this->License_GetLicenseByUserKey(
                            $license->user_key,
                            $license->password_key
                        );
                        if($Response['status'] == 1){
                            $Response['user_key'] = $license->user_key;
                            $Response['password_key'] = $license->password_key; 
                            
                        }
                        return $Response;
                    }
                }
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            return [
                'status' => 1,
                'data' => $license
            ];
        }catch(\Exception $e){
            info('License_GetLicenseByUserKey error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_GetLicenseByUserKeys(
        $credentials
    ){
        try{
            $credentials = collect($credentials);
            $licenses = license::
            select(['id', 'unique_id', 'active', 'name', 'value', 'days_to_expire', 'next_billing_date', 'remaining_days', 'client_id', 'recurrence_months','user_key', 'password_key'])
            ->with(['income_licenses' => function($query){
                $query->select(['id', 'license_id', 'income_id', 'timely_payment', 'license_name', 'service_name', 'recurrence_months']);
                $query->whereHas('income', function($query){
                    $query->whereIn('state', [0, 2]);
                })
                ->with('income', function($query){
                    $query->select(['id', 'unique_id', 'timely_payment', 'cutoff_date', 'total']);
                });
            }])
            ->with(['client' => function($query){
                $query->select(['id', 'name', 'photo', 'identification']);
            }])
            ->where('active', 1)
            ->whereIn('user_key', $credentials->pluck('user_key'))->whereIn('password_key', $credentials->pluck('password_key'))
            ->orderBy('name')
            ->get();
            foreach($credentials as $key => $credential){
                $license = $licenses->where('user_key', $credential['user_key'])->where('password_key', $credential['password_key'])->first();
                $remove = false;
                if($license){
                    $credentials[$key] = $this->License_WebData($license);   
                }else if($credential['id'] != null && $credential['id'] == $credential['user_key']){
                    $license = license::where('id', $credential['id'])->first();
                    if($license){
                        $Response = $this->License_GetLicenseByUserKey(
                            $license->user_key,
                            $license->password_key
                        );
                        if($Response['status'] == 1){
                            $Response['data']['user_key'] = $license->user_key;
                            $Response['data']['password_key'] = $license->password_key;
                            $credentials[$key] = $Response['data'];
                        }else{
                            $remove = true;
                        }
                    }else{
                        $remove = true;
                    }
                }else{
                    $remove = true;
                }
                if($remove){
                    $credentials = $credentials->where('user_key', '!=', $credential['user_key']);
                }
            }
            return [
                'status' => 1,
                'data' => $credentials
            ];
        }catch(\Exception $e){
            info('License_GetLicenseByUserKeys error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Documents
    public function License_AddLicenseDocument(
        $license_id
        ,$name
        ,$file
    ){
        try{
            $license = license::find($license_id)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $accepted_format = ['pdf','docx','xlsx','pptx'];
            $file_format = strtolower($file->getClientOriginalExtension());
            if(($accepted_format == null || in_array($file_format, $accepted_format))){
                $uid = strtoupper(Str::uuid()->toString()).'.'.$file_format;
                Storage::disk('license_document')->put($uid, file_get_contents($file));
                $document = new license_document();
                $document->license_id = $license_id;
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
            info('License_AddLicenseDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_GetLicenseDocuments(
        $license_id
        ,$search
    ){
        try{
            $documents = license_document::where('license_id', $license_id)->orderBy('document_public_name');
            if($search != null && $search != ''){
                $documents = $documents->where('document_public_name', 'like', '%'.$search.'%');
            }
            $documents = $documents->get();
            foreach($documents as $document){
                $document->document_url = Storage::disk('license_document')->url($document->document_private_name);
            }
            return [
                'status' => 1,
                'message' => 'Documentos obtenidos',
                'data' => $documents
            ];
        }catch(\Exception $e){
            info('License_GetLicenseDocuments error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_UpdateLicenseDocument(
        $id
        ,$name
    ){
        try{
            $document = license_document::where('id', $id)->first();
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
            info('License_UpdateLicenseDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_DeleteLicenseDocument(
        $id
    ){
        try{
            $document = license_document::where('id', $id)->first();
            if(!$document){
                return [
                    'status' => 0,
                    'message' => 'El documento no existe'
                ];
            }
            Storage::disk('license_document')->delete($document->document_private_name);
            $document->delete();
            return [
                'status' => 1,
                'message' => 'Documento eliminado'
            ];
        }catch(\Exception $e){
            info('License_DeleteLicenseDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_AddLicenseNotification(
        $license_id
        ,$email
        ,$phone
        ,$active
    ){
        try{
            $license = license::where('id', $license_id)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license_notification = new license_notification();
            $license_notification->license_id = $license_id;
            $license_notification->email = $email;
            $license_notification->phone = $phone;
            $license_notification->active = $active;
            $license_notification->save();
            return [
                'status' => 1,
                'message' => 'Notificacion agregada',
                'id' => $license_notification->id
            ];
        }catch(\Exception $e){
            info('License_AddLicenseNotification error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_GetLicenseNotifications(
        $license_id
        ,$search
        ,$with_trashed = false
    ){
        try{
            $notifications = license_notification::where('license_id', $license_id)->orderBy('email');
            if($search != null && $search != ''){
                $notifications = $notifications->where('email', 'like', '%'.$search.'%');
            }
            if($with_trashed){
                $notifications = $notifications->withTrashed();
            }
            $notifications = $notifications->get();
            return [
                'status' => 1,
                'message' => 'Notificaciones obtenidas',
                'data' => $notifications
            ];
        }catch(\Exception $e){
            info('License_GetLicenseNotifications error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_GetLicenseNotificationsByLicensesIds(
        $license_ids
    ){
        try{
            //Filter by current client
            if(Session::has('client_user')){
                $client_id = Session::get('client_user')['active_client']['id'];
                $licenses = license::whereIn('id', $license_ids)->where('client_id', $client_id)->get();
                if($licenses->count() != count($license_ids)){
                    return [
                        'status' => 0,
                        'message' => 'No se encontraron todas las licencias'
                    ];
                }
            }
            $notifications = license_notification::whereIn('license_id', $license_ids)->orderBy('email')->get();
            //get unique notifications by email
            $notifications = $notifications->unique('email');
            return [
                'status' => 1,
                'message' => 'Notificaciones obtenidas',
                'data' => $notifications
            ];
        }catch(\Exception $e){
            info('License_GetLicenseNotificationsByLicensesIds error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_UpdateLicenseNotification(
        $id
        ,$email
        ,$phone
        ,$active
    ){
        try{
            $notification = license_notification::where('id', $id)->first();
            if(!$notification){
                return [
                    'status' => 0,
                    'message' => 'La notificacion no existe'
                ];
            }
            $notification->email = $email;
            $notification->phone = $phone;
            $notification->active = $active;
            $notification->save();
            return [
                'status' => 1,
                'message' => 'Notificacion actualizada'
            ];
        }catch(\Exception $e){
            info('License_UpdateLicenseNotification error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_DeleteLicenseNotification(
        $id
    ){
        try{
            $notification = license_notification::where('id', $id)->first();
            if(!$notification){
                return [
                    'status' => 0,
                    'message' => 'La notificacion no existe'
                ];
            }
            $notification->delete();
            return [
                'status' => 1,
                'message' => 'Notificacion eliminada'
            ];
        }catch(\Exception $e){
            info('License_DeleteLicenseNotification error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_RestoreLicenseNotification(
        $id
    ){
        try{
            $notification = license_notification::where('id', $id)->withTrashed()->first();
            if(!$notification){
                return [
                    'status' => 0,
                    'message' => 'La notificacion no existe'
                ];
            }
            $notification->restore();
            return [
                'status' => 1,
                'message' => 'Notificacion restaurada'
            ];
        }catch(\Exception $e){
            info('License_RestoreLicenseNotification error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function License_ChangeLicenseNotificationPosition(
        $id
        ,$direction
    ){
        try{
            $license_notification = license_notification::find($id);
            if(!$license_notification){
                return [
                    'status' => 0,
                    'message' => 'No se encontró el Notificación 1.'
                ];
            }
            $license_notification_2 = null;
            if($direction == 'up'){
                $license_notification_2 = license_notification::where('id', $license_notification->id)->where('position', '<', $license_notification->position)->orderBy('position', 'desc')->first();
            }else{
                $license_notification_2 = license_notification::where('id', $license_notification->id)->where('position', '>', $license_notification->position)->orderBy('position')->first();
            }
            if(!$license_notification_2){
                return [
                    'status' => 0,
                    'message' => 'No se encontró el Notificación 2.'
                ];
            }
            $position = $license_notification->position;
            $license_notification->position = $license_notification_2->position;
            $license_notification_2->position = $position;
            $license_notification->save();
            $license_notification_2->save();
            return [
                'status' => 1,
                'message' => 'Notificaciónes actualizados correctamente.',
                'data' => $license_notification
            ];
        }catch(\Exception $e){
            info('License_ChangeLicenseNotificationPosition error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Update license billing data
    public function License_UpdateBillingData(
        $license_id
        ,$next_billing_date
        ,$last_billing_date
    ){
        try{
            $license = license::where('id', $license_id)->where('type', 1)->first();
            if(!$license){
                return [
                    'status' => 0,
                    'message' => 'La licencia no existe'
                ];
            }
            $license->next_billing_date = $next_billing_date;
            $license->last_billing_date = $last_billing_date;
            $license->save();
            return [
                'status' => 1,
                'message' => 'Datos de facturación actualizados'
            ];
        }catch(\Exception $e){
            info('License_UpdateBillingData error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Update licenses billing data by ids
    public function License_UpdateBillingDataByIds(
        $income_licenses
    ){
        try{
            $licenses = license::whereIn('id', $income_licenses->pluck('license_id'))->where('type', 1)->get();
            $license_notifications = license_notification::whereIn('license_id', $licenses->pluck('id'))->get();
            foreach ($income_licenses as $income_license) {
                $license = $licenses->where('id', $income_license->license_id)->first();
                if ($license) {
                    if ($license['last_payed_date'] == null) {
                        $license['last_payed_date'] = Carbon::parse($income_license->timely_payment);
                    } else {
                        $license['last_payed_date'] = Carbon::parse($license['last_payed_date']);
                        $income_license->timely_payment = Carbon::parse($income_license->timely_payment);
                        //Check if last payed date is less than timely payment
                        if ($license['last_payed_date']->lessThanOrEqualTo($income_license->timely_payment)) {
                            $license['last_payed_date'] = $income_license->timely_payment;
                            $license['next_billing_date'] = Carbon::parse($license['last_payed_date'])->addMonths($license['recurrence_months']);
                        }
                    }
                    $license['remaining_days'] = $this->Licese_CalculateRemainingDays($license['last_payed_date'], $license['recurrence_months'], $license['next_billing_date']);
                    $license->save();
                    //Send update notifications
                    $notifications = $license_notifications->where('license_id', $license->id)->values()->all();
                    $Message = 'Hemos actualizado tu licencia '.$license->name.' Próxima fecha de facturación: '.Carbon::parse($license->next_billing_date)->format('Y-m-d');
                    foreach ($notifications as $notification) {
                        if ($notification->email != null && $notification->email != '') {
                            /*$this->twilio_sms_trait->SendEmail(
                                $notification->email
                                , 'Notificación de pago'
                                , 'Se ha realizado un pago de la licencia '.$license->name.' por un valor de '.$license->value.'.'
                            );*/
                        }
                        if ($notification['phone'] != null && $notification['phone'] != '') {
                            $this->TwilioSMS_SendMessage('+57', $notification['phone'], $Message);
                        }
                    }
                }
            }

            
            return [
                'status' => 1,
                'message' => 'Datos de facturación actualizados'
            ];
        }catch(\Exception $e){
            info('License_UpdateBillingDataByIds error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Update licenses remaining days
    public function Licenses_UpdateRemainingDays(){
        try{
            $licenses = license::where('active', 1)->where('type', 1)->get();
            foreach ($licenses as $license) {
                $license->remaining_days = $this->Licese_CalculateRemainingDays($license['last_payed_date'], $license['recurrence_months'], $license['next_billing_date']);
                $license->save();
            }
            return [
                'status' => 1,
                'message' => 'Dias restantes actualizados'
            ];
        }catch(\Exception $e){
            info('Licenses_UpdateRemainingDays error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Get licenses to bill
    function Licenses_GetLicensesToBill(
        $year
        , $month
    ){
        try{
            $licenses = 
            license::
            where('type', 1)
            ->where('active', 1)
            ->where('value', '>', 0)
            ->get()
            ;
            $clients = client::whereIn('id', $licenses->pluck('client_id'))->where('active', 1)->get();
            $licenses = $licenses->filter(function($license) use ($clients, $month, $year){
                //if client is not active
                if($clients->where('id', $license->client_id)->count() == 0){
                    return false;
                }
                //
                if($license->last_billing_date != null){
                    //If the billing date match with de recurrence months
                    $last_billing_date = Carbon::parse($license->last_billing_date)->startOfDay();
                    $month_diff = $last_billing_date->diffInMonths(Carbon::parse($year.'-'.$month.'-'.$license->billing_day)->endOfDay());
                    if($month_diff == $license->recurrence_months || $license->recurrence_months == 1){
                        return true;
                    }
                }else{
                    //Uso of case when it is a new license
                    return true;
                }
                return false;
            });
            $services = service::whereIn('id', $licenses->pluck('service_id'))->get();
            $taxes = tax::whereIn('id', $services->pluck('tax_id'))->get();
            $employees = employee::whereIn('id', $licenses->pluck('employee_id'))->get();
            $licenses = $licenses->map(function($license) use ($clients, $services, $taxes, $employees, $month, $year){
                //Assoc related data to the register
                $license->client = $clients->where('id', $license->client_id)->first();
                $service = $services->where('id', $license->service_id)->first();
                $service->tax = $taxes->where('id', $service->tax_id)->first();
                $license->service = $service;
                $license->employee = $employees->where('id', $license->employee_id)->first();
                //Set billing date and next billing date
                $license->last_billing_date = Carbon::parse($year.'-'.$month.'-'.$license->billing_day);
                $license->next_billing_date = $license->last_billing_date->copy()->addMonths($license->recurrence_months);
                //Set cuttoff date
                $license->cutoff_date = $license->last_billing_date->copy()->addDays($license->days_to_expire);
                return $license;
            });
            return [
                'status' => 1,
                'message' => 'Licencias obtenidas',
                'data' => [
                    'licenses' => $licenses
                    ,'clients' => $clients
                ]
            ];
        }catch(\Exception $e){
            info('License_GetMonthLicensesToBill error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Licenses_GetExpireLicenses(){
        try{
            $licenses = license::query()
            // Filter by clients that are active
            ->whereHas('client', function($query) {
                $query->where('active', 1);
            })
            // Load the related 'client'
            ->with('client')
            // Filter licenses based on associated income states
            ->whereHas('income_licenses', function($query) {
                $query->whereHas('income', function($query) {
                    $query->whereIn('state', [2]);
                });
            })
            // Load the related 'income_licenses' and their 'income', ordered by 'cutoff_date'
            ->with(['income_licenses' => function($query) {
                $query->whereHas('income', function($query) {
                    $query->whereIn('state', [2])
                          ->orderBy('cutoff_date', 'desc');
                })
                // Eager load income within income_licenses
                ->with(['income' => function($query) {
                    $query->whereIn('state', [2]);
                }]);
            }])
            // Filter and load active 'license_notifications'
            ->whereHas('license_notifications', function($query) {
                $query->where('active', 1);
            })
            ->with('license_notifications')
            // Load the related 'service'
            ->with('service')
            // Additional license filters
            ->where('active', 1)
            ->where('type', 1)
            ->where('remaining_days', '<', 2)
            ->get();
            return [
                'status' => 1,
                'message' => 'Licencias vencidas obtenidas',
                'data' => collect($licenses)
            ];
        }catch(\Exception $e){
            info('Licenses_GetExpireLicenses error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //STATISTICS
    public function License_StatisticGetActiveLicenses(){
        try{
            $licenses_count = license::where('active', 1)->count();
            return [
                'status' => 1,
                'message' => 'Licencias activas obtenidas',
                'data' => $licenses_count
            ];
        }catch(\Exception $e){
            info('License_StatisticGetActiveLicenses error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_StatisticGetLicencesDues(){
        try{
            $licenses = license::
            with(['client' => function($query){
                $query->select(['id', 'name', 'photo']);
            }])
            ->where('active', 1)
            ->where('type', 1)
            ->where('remaining_days', '<=', 0)
            ->orderBy('remaining_days')
            ->get();
            return [
                'status' => 1,
                'message' => 'Licencias vencidas obtenidas',
                'data' => $licenses
            ];
        }catch(\Exception $e){
            info('License_StatisticGetLicencesDues error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function License_GetLicensesByDateRangeReport(
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
            $Reponse = $this->License_GetLicensesByDateRange($date_from, $date_to);
            if($Reponse['status'] == 0){
                return $Reponse;
            }
            $licenses = $Reponse['data'];
            //group by service
            $report = $licenses->groupBy('service.name');
            $date_diff = $date_to->diffInDays($date_from);
            if ($date_diff < 31) {
                $all_days = collect();
                $current_day = $date_from->copy();
                
                while ($current_day->lessThanOrEqualTo($date_to)) {
                    $all_days->put($current_day->format('Y-m-d'), [
                        'label' => $current_day->format('d M Y'),
                        'total' => 0
                    ]);
                    $current_day->addDay();
                }
            
                $labels = $all_days->map(function ($day) {
                    return $day['label'];
                });
            
                $report = $report->map(function ($service) use ($all_days) {
                    $service = $service->groupBy(function ($license): string {
                        return Carbon::parse($license->created_at)->format('d M Y');
                    })->map(function ($grupped_licenses) {
                        // Return the count of licenses per day
                        return [
                            'label' => $grupped_licenses->first()->created_at->format('d M Y') . ' - ' . $grupped_licenses->count(),
                            'total' => $grupped_licenses->count()
                        ];
                    });
            
                    // Merge with all_days to ensure all days are represented
                    $service = $all_days->map(function($day) use ($service) {
                        // If the day exists in the report, update the total
                        if ($service->has($day['label'])) {
                            $day['total'] = $service->get($day['label'])['total'];
                        }
                        return $day;
                    });
                    return $service;
                });
            } elseif ($date_diff < 365) {
                // Sum licenses by month
                $all_months = collect();
                $current_month = $date_from->copy();
            
                while ($current_month->lessThanOrEqualTo($date_to)) {
                    $all_months->put($current_month->format('Y-m'), [
                        'label' => $current_month->format('M Y'),
                        'total' => 0
                    ]);
                    $current_month->addMonth();
                }
                $labels = $all_months->map(function ($month) {
                    return $month['label'];
                });
                $report = $report->map(function ($service) use ($all_months) {
                    $service = $service->groupBy(function ($license): string {
                        return Carbon::parse($license->created_at)->format('M Y');
                    })->map(function ($grupped_licenses) {
                        // Return the count of licenses per month
                        return [
                            'label' => $grupped_licenses->first()->created_at->format('M Y') . ' - ' . $grupped_licenses->count(),
                            'total' => $grupped_licenses->count()
                        ];
                    });
                    // Merge with all_months to ensure all months are represented
                    $service = $all_months->map(function($month) use ($service) {
                        // If the month exists in the report, update the total
                        if ($service->has($month['label'])) {
                            $month['total'] = $service->get($month['label'])['total'];
                        }
                        return $month;
                    });
                    
                    return $service;
                });
            } else {
                // Sum licenses by year
                $all_years = collect();
                $current_year = $date_from->copy();
            
                while ($current_year->lessThanOrEqualTo($date_to)) {
                    $all_years->put($current_year->format('Y'), [
                        'label' => $current_year->format('Y'),
                        'total' => 0
                    ]);
                    $current_year->addYear();
                }
                $labels = $all_years->map(function ($year) {
                    return $year['label'];
                });
                $report = $report->map(function ($service) use ($all_years) {
                    $service = $service->groupBy(function ($license): string {
                        return Carbon::parse($license->created_at)->format('Y');
                    })->map(function ($grupped_licenses) {
                        // Return the count of licenses per year
                        return [
                            'label' => $grupped_licenses->first()->created_at->format('Y') . ' - ' . $grupped_licenses->count(),
                            'total' => $grupped_licenses->count()
                        ];
                    });
            
                    // Merge with all_years to ensure all years are represented
                    $service = $all_years->map(function($year) use ($service) {
                        // If the year exists in the report, update the total
                        if ($service->has($year['label'])) {
                            $year['total'] = $service->get($year['label'])['total'];
                        }
                        return $year;
                    });
                    return $service;
                });
            }
            Storage::disk('reports')->put('licenses'.Session::get('user')['unique_id'].'.json', json_encode($licenses));
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de licensees obtenido',
                'data' => [
                    'licenses' => $licenses,
                    'report' => $report,
                    'labels' => $labels
                ]
            ];
        }catch(\Exception $e){
            info('License_GetLicensesByDateRangeReport error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function License_GetLicensesByDateRange(
        $date_from
        ,$date_to
    ){
        try{
            $licenses = license::
            with(['client' => function($query){
                $query->select(['id', 'name', 'photo']);
            }, 'service' => function($query){
                $query->select(['id', 'name']);
            }, 'employee'])
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->orderBy('created_at', 'asc')
            ->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $licenses
            ];
        }catch(\Exception $e){
            info('License_GetLicensesByDateRange error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}