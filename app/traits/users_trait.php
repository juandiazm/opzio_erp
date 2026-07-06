<?php 
namespace App\traits;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;

use App\Models\user;
use App\Models\user_permission;
use App\Models\user_permission_assoc;
use App\Models\user_traceability;

use Session;


trait users_trait
{
    use 
    mail_trait
    , twilio_sms_trait
    ;
    private $URL_USERS_PATH = 'images/erp/users/';
    //Users
    public function User_GetAllUsers(){
        try{
            $users = user::orderBy('name', 'desc')->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $users
            ];
        }catch(\Exception $e){
            info('User_GetAllUsers error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_NextId(){
        try{
            $nextId = user::query()->max('id') + 1;
            return [
                'status' => 1,
                'message' => 'Id obtenido',
                'data' => $nextId
            ];
            
        }catch(\Exception $e){
            info('User_NextId error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_AddUser(
        $name
        ,$lastname
        ,$username
        ,$email
        ,$identification
        ,$password
        ,$photo
        ,$color
        ,$permissions
    ){
        try{
            $user = user::where('email', $email)->orWhere('identification', $identification)->orWhere('username', $username)->first();
            if($user){
                return [
                    'status' => 0,
                    'message' => 'El usuario ya existe'
                ];
            }
            $user = new user();
            $user->unique_id = strtoupper(Str::uuid()->toString());
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $user->photo = $user->unique_id.'.webp';
                $photo->save($this->URL_USERS_PATH . $user->photo);
                ImageOptimizer::optimize($this->URL_USERS_PATH . $user->photo);
            }
            $user->name = $name;
            $user->lastname = $lastname;
            $user->username = $username;
            $user->email = $email;
            $user->identification = $identification;
            $user->password = Hash::make($password);
            $user->color = $color;
            $user->save();
            //Permissions
            foreach($permissions as $key => $permission){
                $user_permission = new user_permission_assoc();
                $user_permission->user_id = $user->id;
                $user_permission->user_permission_id = $key;
                $user_permission->save();
            }
            $nextId = $user->id+1;
            return [
                'status' => 1,
                'message' => 'Usuario agregado',
                'nextId' => $nextId
            ];
        }catch(\Exception $e){
            info('User_AddUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_UpdateUser(
        $id
        ,$name
        ,$lastname
        ,$username
        ,$email
        ,$identification
        ,$password
        ,$photo
        ,$color
        ,$permissions
    ){
        try{
            $user = user::where('id', $id)->first();
            if(!$user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $user->name = $name;
            $user->lastname = $lastname;
            $user->username = $username;
            $user->email = $email;
            $user->identification = $identification;
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $user->photo = $user->unique_id.'.webp';
                $photo->save($this->URL_USERS_PATH . $user->photo);
                ImageOptimizer::optimize($this->URL_USERS_PATH . $user->photo);
            }
            $user->color = $color;
            $user->save();
            //Permissions
            user_permission_assoc::where('user_id', $user->id)->delete();
            foreach($permissions as $key => $permission){
                $user_permission = new user_permission_assoc();
                $user_permission->user_id = $user->id;
                $user_permission->user_permission_id = $key;
                $user_permission->save();
            }
            return [
                'status' => 1,
                'message' => 'Usuario actualizado'
            ];
        }catch(\Exception $e){
            info('User_UpdateUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_GetUserById(
        $id
    ){
        try{
            $user = user::where('id', $id)->first();
            if(!$user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $permissions = user_permission_assoc::where('user_id', $user->id)->get();
            $user->permissions = $permissions;
            return [
                'status' => 1,
                'message' => 'Usuario obtenido',
                'data' => $user
            ];
        }catch(\Exception $e){
            info('User_GetUserById error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_LoginUser(
        $identification
        ,$password
    ){
        try{
            $user = user::where('email', $identification)->orWhere('identification', $identification)->orWhere('username', $identification)->first();
            if(!$user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            if($user->password == null || !Hash::check($password, $user->password)){
                if($user->reset_password_date!=null && $user->reset_password != null){
                    if(Carbon::now()->gt($user->reset_password_date)){
                        return [
                            'status' => 0,
                            'message' => 'La contraseña temporal ha expirado'
                        ];
                    }else if(!Hash::check($password, $user->reset_password)){
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
            $permissions = user_permission_assoc::where('user_id', $user->id)->get();
            Session::put('user', $user);
            Session::put('permissions', $permissions);
            return [
                'status' => 1,
                'message' => 'Usuario logueado'
            ];
        }catch(\Exception $e){
            info('User_LoginUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_GetPage(
        $pagination
        ,$search
        ,$withTrash = false
    ){
        try{
            if($withTrash){
                $users = user::withTrashed()->orderBy('id', 'desc');
            }else{
                $users = user::orderBy('id', 'desc');
            }
            if($search != null && $search != ''){
                $users = $users->where('unique_id', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('lastname', 'like', '%'.$search.'%')
                ->orWhere('username', 'like', '%'.$search.'%')
                ->orWhere('identification', 'like', '%'.$search.'%')
                ;
            }
            $pagination['total'] = $users->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $users = $users->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            $permissions = user_permission_assoc::whereIn('user_id', $users->pluck('id'))->get();
            foreach($users as $key => $user){
                $user->permissions = $permissions->where('user_id', $user->id)->values()->all();
            }
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'pagination' => $pagination,
                'data' => $users
            ];
        }catch(\Exception $e){
            info('User_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_CloseSession(){
        try{
            Session::forget('user');
            Session::forget('app_permissions');
            return [
                'status' => 1,
                'message' => 'Sesión cerrada'
            ];
        }catch(\Exception $e){
            info('User_CloseSession error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_DeleteUser(
        $id
    ){
        try{
            $user = user::where('id', $id)->first();
            if(!$user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $user->delete();
            return [
                'status' => 1,
                'message' => 'Usuario eliminado'
            ];
        }catch(\Exception $e){
            info('User_DeleteUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_RestoreUser(
        $id
    ){
        try{
            $user = user::withTrashed()->where('id', $id)->first();
            if(!$user){
                return [
                    'status' => 0,
                    'message' => 'El usuario no existe'
                ];
            }
            $user->restore();
            return [
                'status' => 1,
                'message' => 'Usuario restaurado'
            ];
        }catch(\Exception $e){
            info('User_RestoreUser error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_ForgotPassword(
        $identification
    ){
        try{
            $user = user::where('identification', $identification)->orWhere('email', $identification)->first();
            if(!$user){
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
                'address' => $user->email,
                'name' => $user->name
            ];
            $MailData = 
            [
                'subject' => 'Restauración de contraseña'
            ];
            $View = 'mail.user_restore_password';
            $ViewData = collect(
            [
                "name"=> $user->name,
                "email"=> $user->email,
                "restore-code" => $password,
                "reset_password_date" => $reset_password_date,
            ]
            );
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            if($MailResponse['status'] == 0){
                return $MailResponse;
            }
            ////////////////////////////
            $user->reset_password = Hash::make($password);
            $user->reset_password_date = $reset_password_date;
            $user->save();
            if($user['phone']){
                $this->TwilioSMS_SendMessage(
                    '+57',
                    $user['phone'],
                    'Hola '.$user['name'].'. Tu código de restauración de contraseña es: '.$password.' y expira el '.$reset_password_date->format('Y-m-d h:i A')
                );
            }
            return [
                'status' => 1,
                'message' => ''
            ];
        }catch(\Exception $e){
            info('User_ForgotPassword error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    function User_ResetPassword(
        $password
    ){
        try{
            $user = Session::get('user');
            $user->password = Hash::make($password);
            $user->reset_password = null;
            $user->reset_password_date = null;
            $user->save();
            Session::forget('temporal_password');
            return [
                'status' => 1,
                'message' => 'Contraseña actualizada'
            ];
        }catch(\Exception $e){
            info('User_ResetPassword error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    //User permissions
    public function UserPermissions_GetPermissions(){
        try{
            $permissions = user_permission::orderBy('id', 'asc')->get();
            return [
                'status' => 1,
                'message' => 'Permisos obtenidos',
                'data' => $permissions
            ];
        }catch(\Exception $e){
            info('UserPermissions_GetPermissions error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Traceability
    public function UserPermissions_GetTraceability(
        $pagination,
        $user_id,
        $search,
        $date_from,
        $date_to,
        $url = null
    ){
        try{
            if($user_id == null || $user_id == 0){
                $traceability = user_traceability::orderBy('id', 'desc');
            }else{
                $traceability = user_traceability::where('user_id', $user_id)->orderBy('id', 'desc');
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
            $users = user::whereIn('id', $traceability->pluck('user_id'))->get();
            foreach($traceability as $key => $trace){
                $trace->user = $users->where('id', $trace->user_id)->first();
            }
            return [
                'status' => 1,
                'message' => 'Traza obtenida',
                'pagination' => $pagination,
                'data' => $traceability
            ];
        }catch(\Exception $e){
            info('UserPermissions_GetTraceability error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function User_GetUsersByDateRangeReport(
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
            $Reponse = $this->User_GetUsersByDateRange($date_from, $date_to);
            if($Reponse['status'] == 0){
                return $Reponse;
            }
            $users = $Reponse['data'];
            $date_diff = $date_to->diffInDays($date_from);
            if($date_diff < 31){
                $report = $users->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('Y-m-d');
                })->map(function($grupped_users) {
                    // Return the count of users per day
                    return [
                        'label' => $grupped_users->first()->created_at->format('d M Y'). ' - '.$grupped_users->count(),
                        'total' => $grupped_users->count()
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
                //sum user by month
                $report = $users->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('M Y');
                })->map(function($grupped_users) {
                    // Return the count of users per month
                    return [
                        'label' => $grupped_users->first()->created_at->format('M Y'). ' - '.$grupped_users->count(),
                        'total' => $grupped_users->count()
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
                //sum user by year
                $report = $users->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('Y');
                })->map(function($grupped_users) {
                    // Return the count of users per year
                    return [
                        'label' => $grupped_users->first()->created_at->format('Y'). ' - '.$grupped_users->count(),
                        'total' => $grupped_users->count()
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
            Storage::disk('reports')->put('users'.Session::get('user')['unique_id'].'.json', json_encode($users));
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de usuarios obtenido',
                'data' => [
                    'users' => $users,
                    'report' => $report
                ]
            ];
        }catch(\Exception $e){
            info('User_GetUsersByDateRangeReport error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function User_GetUsersByDateRange(
        $date_from
        ,$date_to
    ){
        try{
            $users = user::
            whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->orderBy('created_at', 'asc')
            ->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $users
            ];
        }catch(\Exception $e){
            info('User_GetUsersByDateRange error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}