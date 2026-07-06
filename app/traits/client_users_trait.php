<?php 
namespace App\traits;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;

use App\Models\client_user;
use App\Models\client_user_permission;
use App\Models\client_user_permission_assoc;
use App\Models\client_user_traceability;
use App\Models\client_user_assoc;
use App\Models\client;
use App\Models\country;
use App\Models\sector;

use Session;


trait client_users_trait
{
    use 
    mail_trait
    , twilio_sms_trait
    ;
    private $URL_USERS_PATH = 'images/erp/client_users/';
    //ClientUsers
    public function ClientUser_GetAllClientUsers(){
        try{
            $client_users = client_user::orderBy('name', 'desc')->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $client_users
            ];
        }catch(\Exception $e){
            info('ClientUser_GetAllClientUsers error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Client users by client id
    public function ClientUser_GetAllClientUsersByClientId(
        $client_id
    ){
        try{
            $client_user_assoc = client_user_assoc::where('client_id', $client_id)->get();
            $client_users = client_user::whereIn('id', $client_user_assoc->pluck('client_user_id'))->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $client_users
            ];
        }catch(\Exception $e){
            info('ClientUser_GetAllClientUsersByClientId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_NextId(){
        try{
            $nextId = client_user::query()->max('id') + 1;
            return [
                'status' => 1,
                'message' => 'Id obtenido',
                'data' => $nextId
            ];
            
        }catch(\Exception $e){
            info('ClientUser_NextId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_AddClientUser(
        $client_id
        ,$name
        ,$lastname
        ,$username
        ,$email
        ,$phone
        ,$position
        ,$color
        ,$permissions
        ,$send_client_email = true
    ){
        try{
            $client_user = client_user::where('email', $email)->orWhere('username', $username)->first();
            if($client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario ya existe'
                ];
            }
            //pasword with 6 random characters
            $password = rand(100000, 999999);
            ////////////////////////////
            $client_user = new client_user();
            $client_user->unique_id = strtoupper(Str::uuid()->toString());
            $client_user->name = $name;
            $client_user->lastname = $lastname;
            $client_user->username = $username;
            $client_user->email = $email;
            $client_user->phone = $phone;
            $client_user->position = $position;
            $client_user->reset_password = Hash::make($password);
            $client_user->reset_password_date = Carbon::now()->addMinutes(120);
            $client_user->color = $color;
            $client_user->save();
            //Permissions
            if($permissions){
                foreach($permissions as $key => $permission){
                    $client_user_permission = new client_user_permission_assoc();
                    $client_user_permission->client_user_id = $client_user->id;
                    $client_user_permission->client_user_permission_id = $permission;
                    $client_user_permission->save();
                }
            }
            //
            $client_user_assoc = client_user_assoc::where('client_id', $client_id)->where('client_user_id', $client_user->id)->first();
            if(!$client_user_assoc){
                $client_user_assoc = new client_user_assoc();
                $client_user_assoc->client_id = $client_id;
                $client_user_assoc->client_user_id = $client_user->id;
                $client_user_assoc->save();
            }
            if($send_client_email == true){
                //Send email to client
                $client = client::where('id', $client_id)->first();
                if($client->active == 1){
                    $Mails = [];
                    $Mails[] = [
                        'address' => $client->email,
                        'name' => $client->name
                    ];
                    $MailData = 
                    [
                        'subject' => 'Nuevo usuario agregado'
                    ];
                    $View = 'mail.client_user_added';
                    $ViewData = collect(
                    [
                        "client_name"=> $client->name,
                        "client_user_name" => $client_user->name.' '.$client_user->lastname,
                        "client_user_email" => $client_user->email
                    ]
                    );
                    $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
                }
                ////////////////////////////
                //Send email to new user
                $Mails = [];
                $Mails[] = [
                    'address' => $client_user->email,
                    'name' => $client_user->name
                ];
                $MailData = 
                [
                    'subject' => 'Bienvenido a Opzio S.A.S - Contraseña temporal'
                ];
                $View = 'mail.client_user_welcome';
                $ViewData = collect(
                [
                    "name"=> $client_user->name,
                    "email"=> $client_user->email,
                    "password" => $password,
                    "reset_password_date" => $client_user->reset_password_date
                ]
                );
                $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
                //Send SMS
                if($client_user['phone']){
                    $this->TwilioSMS_SendMessage(
                        '+57',
                        $client_user['phone'],
                        'Hola '.$client_user['name'].'. Tu contraseña temporal es: '.$password
                    );
                }
            }
            return [
                'status' => 1,
                'message' => 'Usuario agregado',
                'data' => [
                    'password' => $password,
                    'mailResponse' => $MailResponse,
                    'client_user' => $client_user
                ],
                
            ];
        }catch(\Exception $e){
            info('ClientUser_AddClientUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_UpdateClientUser(
        $id
        ,$name
        ,$lastname
        ,$username
        ,$email
        ,$phone
        ,$position
        ,$color
        ,$permissions
    ){
        try{
            $client_user = client_user::find($id);
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $client_user->name = $name;
            $client_user->lastname = $lastname;
            $client_user->username = $username;
            $client_user->email = $email;
            $client_user->phone = $phone;
            $client_user->position = $position;
            $client_user->color = $color;
            $client_user->save();
            //Permissions
            if($permissions){
                $current_permissions = client_user_permission_assoc::where('client_user_id', $client_user->id)->get();
                $to_delete_permissions = $current_permissions->filter(function($permission) use ($permissions){
                    return !in_array($permission['client_user_permission_id'], $permissions);
                });
                $permissions = collect($permissions);
                $to_create_permissions = $permissions->filter(function($permission) use ($current_permissions){
                    return $current_permissions->where('client_user_permission_id', $permission)->count() == 0;
                });
                foreach($to_delete_permissions as $key => $permission){
                    $permission->delete();
                }
                foreach($to_create_permissions as $key => $permission){
                    $client_user_permission = new client_user_permission_assoc();
                    $client_user_permission->client_user_id = $client_user->id;
                    $client_user_permission->client_user_permission_id = $permission;
                    $client_user_permission->save();
                }
            }
            return [
                'status' => 1,
                'message' => 'Usuario actualizado'
            ];
        }catch(\Exception $e){
            info('ClientUser_UpdateClientUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_UpdateClientUserProfile(
        $id
        ,$name
        ,$lastname
        ,$username
        ,$email
        ,$phone
        ,$position
        ,$color
        ,$password
        ,$permissions
    ){
        try{
            $client_user = client_user::find($id);
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            //check if email or username already exists
            $client_user_check = client_user::where(function($query) use ($email, $username){
                $query->where('email', $email)
                ->orWhere('username', $username);
            })->where('id', '!=', $id)->first();
            if($client_user_check){
                return [
                    'status' => 0,
                    'message' => 'El email o el nombre de usuario ya existe'
                ];
            }
            $client_user->name = $name;
            $client_user->lastname = $lastname;
            $client_user->username = $username;
            $client_user->email = $email;
            $client_user->phone = $phone;
            $client_user->position = $position;
            $client_user->color = $color;
            $client_user->save();
            //Permissions
            if($permissions){
                $current_permissions = client_user_permission_assoc::where('client_user_id', $client_user->id)->get();
                $to_delete_permissions = $current_permissions->filter(function($permission) use ($permissions){
                    return !in_array($permission['client_user_permission_id'], $permissions);
                });
                $permissions = collect($permissions);
                $to_create_permissions = $permissions->filter(function($permission) use ($current_permissions){
                    return $current_permissions->where('client_user_permission_id', $permission)->count() == 0;
                });
                foreach($to_delete_permissions as $key => $permission){
                    $permission->delete();
                }
                foreach($to_create_permissions as $key => $permission){
                    $client_user_permission = new client_user_permission_assoc();
                    $client_user_permission->client_user_id = $client_user->id;
                    $client_user_permission->client_user_permission_id = $permission;
                    $client_user_permission->save();
                }
                session::put('permissions', client_user_permission_assoc::where('client_user_id', $client_user->id)->get());
            }
            if($password && $password != ''){
                $client_user->password = Hash::make($password);
                $client_user->reset_password = null;
                $client_user->reset_password_date = null;
                $client_user->save();
            }
            Session::forget('client_user');
            return [
                'status' => 1,
                'message' => 'Usuario actualizado'
            ];
        }catch(\Exception $e){
            info('ClientUser_UpdateClientUserProfile error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_GetClientUserById(
        $id
    ){
        try{
            $client_user = client_user::where('id', $id)->first();
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $permissions = client_user_permission_assoc::where('client_user_id', $client_user->id)->get();
            $client_user->permissions = $permissions;
            return [
                'status' => 1,
                'message' => 'Usuario obtenido',
                'data' => $client_user
            ];
        }catch(\Exception $e){
            info('ClientUser_GetClientUserById error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_GetClientUsersByClientId(
        $client_id
    ){
        try{
            $client_user_assoc = client_user_assoc::where('client_id', $client_id)->get();
            $client_users = client_user::whereIn('id', $client_user_assoc->pluck('client_user_id'))->withTrashed()->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $client_users
            ];
        }catch(\Exception $e){
            info('ClientUser_GetClientUsersByClientId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_LoginClientUser(
        $identification
        ,$password
    ){
        try{
            $client_user = client_user::where('email', $identification)->orWhere('username', $identification)->first();
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            Session::put('temporal_password', 0);
            if($client_user->password == null || !Hash::check($password, $client_user->password)){
                if($client_user->reset_password_date!=null && $client_user->reset_password != null){
                    if(Carbon::now()->gt($client_user->reset_password_date)){
                        return [
                            'status' => 0,
                            'message' => 'La contraseña temporal ha expirado'
                        ];
                    }else if(!Hash::check($password, $client_user->reset_password)){
                        return [
                            'status' => 0,
                            'message' => 'La contraseña temporal es incorrecta'
                        ];
                    }else{
                        Session::put('temporal_password', 1);
                    }
                }else{
                    return [
                        'status' => 0,
                        'message' => 'La contraseña es incorrecta'
                    ];
                }
            }
            $permissions = client_user_permission_assoc::where('client_user_id', $client_user->id)->get();
            $client_user_assocs = client_user_assoc::where('client_user_id', $client_user->id)->where('active', 1)->get();
            //get client data from assocs
            $clients = client::whereIn('id', $client_user_assocs->pluck('client_id'))->get();
            //get country data from clients
            $countries = country::whereIn('id', $clients->pluck('country_id'))->get();
            //get sector data from clients
            $sectors = sector::whereIn('id', $clients->pluck('sector_id'))->get();
            foreach($clients as $key => $client){
                $client->country = $countries->where('id', $client->country_id)->first();
                $client->sector = $sectors->where('id', $client->sector_id)->first();
            }
            $client_user_assocs = $client_user_assocs->map(function($assoc) use ($clients){
                $assoc = $clients->where('id', $assoc->client_id)->first();
                return $assoc;
            });
            //Save session data
            $client_user->clients = $client_user_assocs;
            $client_user->active_client = $client_user_assocs->first();
            Session::put('client_user', $client_user);
            Session::put('permissions', $permissions);
            return [
                'status' => 1,
                'message' => 'Usuario logueado'
            ];
        }catch(\Exception $e){
            info('ClientUser_LoginClientUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_GetPage(
        $client_id,
        $pagination,
        $blacklist = []
    ){
        try{
            $client_user_assoc = client_user_assoc::where('client_id', $client_id)->get();
            $client_users = client_user::whereIn('id', $client_user_assoc->pluck('client_user_id'))->whereNotIn('id', $blacklist);
            $pagination['total'] = $client_users->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $client_users = $client_users->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            $permissions = client_user_permission_assoc::whereIn('client_user_id', $client_users->pluck('id'))->get();
            foreach($client_users as $key => $client_user){
                $client_user->permissions = $permissions->where('client_user_id', $client_user->id)->values()->all();
            }
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'pagination' => $pagination,
                'data' => $client_users
            ];
        }catch(\Exception $e){
            info('ClientUser_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_CloseSession(){
        try{
            Session::forget('client_user');
            return [
                'status' => 1,
                'message' => 'Sesión cerrada'
            ];
        }catch(\Exception $e){
            info('ClientUser_CloseSession error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_RestoreClientUserPassword(
        $id
    ){
        try{
            $client_user = client_user::where('id', $id)->first();
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            //pasword with 6 random number digits
            $password = rand(100000, 999999);
            $reset_password_date = Carbon::now()->addMinutes(30);
            //SEND EMAIL
            $Mails = [];
            $Mails[] = [
                'address' => $client_user->email,
                'name' => $client_user->name
            ];
            $MailData = 
            [
                'subject' => 'Restauración de contraseña'
            ];
            $View = 'mail.client_user_restore_password';
            $ViewData = collect(
            [
                "name"=> $client_user->name,
                "email"=> $client_user->email,
                "restore-code" => $password,
                "reset_password_date" => $reset_password_date,
            ]
            );
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            if($MailResponse['status'] == 0){
                return $MailResponse;
            }
            ////////////////////////////
            $client_user->reset_password = Hash::make($password);
            $client_user->reset_password_date = $reset_password_date;
            $client_user->save();
            if($client_user['phone']){
                $this->TwilioSMS_SendMessage(
                    '+57',
                    $client_user['phone'],
                    'Hola '.$client_user['name'].'. Tu código de restauración de contraseña es: '.$password.' y expira el '.$reset_password_date->format('Y-m-d h:i A')
                );
            }
            return [
                'status' => 1,
                'message' => 'Contraseña restaurada',
                'data' => $password
            ];
        }catch(\Exception $e){
            info('ClientUser_RestoreClientUserPassword error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_ForgotPassword(
        $email
    ){
        try{
            $client_user = client_user::where('email', $email)->first();
            if(!$client_user){
                return [
                    'status' => 1,
                    'message' => ''
                ];
            }
            //pasword with 6 random number digits
            $password = rand(100000, 999999);
            $reset_password_date = Carbon::now()->addMinutes(30);
            //SEND EMAIL
            $Mails = [];
            $Mails[] = [
                'address' => $client_user->email,
                'name' => $client_user->name
            ];
            $MailData = 
            [
                'subject' => 'Restauración de contraseña'
            ];
            $View = 'mail.client_user_restore_password';
            $ViewData = collect(
            [
                "name"=> $client_user->name,
                "email"=> $client_user->email,
                "restore-code" => $password,
                "reset_password_date" => $reset_password_date,
            ]
            );
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            if($MailResponse['status'] == 0){
                return $MailResponse;
            }
            ////////////////////////////
            $client_user->reset_password = Hash::make($password);
            $client_user->reset_password_date = $reset_password_date;
            $client_user->save();
            if($client_user['phone']){
                $this->TwilioSMS_SendMessage(
                    '+57',
                    $client_user['phone'],
                    'Hola '.$client_user['name'].'. Tu código de restauración de contraseña es: '.$password.' y expira el '.$reset_password_date->format('Y-m-d h:i A')
                );
            }
            return [
                'status' => 1,
                'message' => ''
            ];
        }catch(\Exception $e){
            info('ClientUser_ForgotPassword error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_DeleteClientUser(
        $id
    ){
        try{
            $client_user = client_user::find($id);
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $client_user->delete();
            return [
                'status' => 1,
                'message' => 'Usuario eliminado'
            ];
        }catch(\Exception $e){
            info('ClientUser_DeleteClientUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_RestoreClientUser(
        $id
    ){
        try{
            $client_user = client_user::withTrashed()->find($id);
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $client_user->restore();
            return [
                'status' => 1,
                'message' => 'Usuario restaurado'
            ];
        }catch(\Exception $e){
            info('ClientUser_RestoreClientUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUser_SetClientUserPassword(
        $id
        ,$password
    ){
        try{
            $client_user = client_user::find($id);
            if(!$client_user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $client_user->password = Hash::make($password);
            $client_user->reset_password = null;
            $client_user->reset_password_date = null;
            $client_user->save();
            Session::put('temporal_password', 0);
            return [
                'status' => 1,
                'message' => 'Contraseña actualizada'
            ];
        }catch(\Exception $e){
            info('ClientUser_SetClientUserPassword error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //ClientUser permissions
    public function ClientUserPermissions_GetAllPermissions(){
        try{
            $permissions = client_user_permission::orderBy('id', 'asc')->get();
            return [
                'status' => 1,
                'message' => 'Permisos obtenidos',
                'data' => $permissions
            ];
        }catch(\Exception $e){
            info('ClientUserPermissions_GetAllPermissions error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUserPermissions_GetPermissions(){
        try{
            $permissions = client_user_permission::orderBy('id', 'asc')->get();
            return [
                'status' => 1,
                'message' => 'Permisos obtenidos',
                'data' => $permissions
            ];
        }catch(\Exception $e){
            info('ClientUserPermissions_GetPermissions error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientUserPermissions_GetPermissionsByUserId(
        $client_user_id
    ){
        try{
            $permissions = client_user_permission_assoc::where('client_user_id', $client_user_id)->get();
            return [
                'status' => 1,
                'message' => 'Permisos obtenidos',
                'data' => $permissions
            ];
        }catch(\Exception $e){
            info('ClientUserPermissions_GetPermissionsByUserId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Traceability
    public function ClientUserPermissions_GetTraceability(
        $pagination,
        $client_user_id,
        $search,
        $date_from,
        $date_to,
        $url = null
    ){
        try{
            if($client_user_id == null || $client_user_id == 0){
                $traceability = client_user_traceability::orderBy('id', 'desc');
            }else{
                $traceability = client_user_traceability::where('client_user_id', $client_user_id)->orderBy('id', 'desc');
            }
            if($search){
                $traceability = $traceability->where(function($query) use ($search){
                    $query->where('action', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%')
                    ->orWhere('ip', 'like', '%'.$search.'%')
                    ->orWhere('payload', 'like', '%'.$search.'%')
                    ;
                });
            }
            if($date_from){
                $traceability = $traceability->whereDate('created_at', '>=', $date_from);
            }
            if($date_to){
                $traceability = $traceability->whereDate('created_at', '<=', $date_to);
            }
            if($url){
                $traceability = $traceability->where('path', 'like', '%'.$url.'%');
            }
            $pagination['total'] = $traceability->count();
            $traceability = $traceability->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $client_users = client_user::withTrashed()->whereIn('id', $traceability->pluck('client_user_id'))->get();
            foreach($traceability as $key => $trace){
                $trace->client_user = $client_users->where('id', $trace->client_user_id)->first();
            }
            return [
                'status' => 1,
                'message' => 'Traza obtenida',
                'pagination' => $pagination,
                'data' => $traceability
            ];
        }catch(\Exception $e){
            info('ClientUserPermissions_GetTraceability error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}