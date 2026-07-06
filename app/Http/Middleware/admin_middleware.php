<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Str;


use App\Models\user_permission;
use App\Models\user_traceability;

class admin_middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $action = $request->method();
        $path = $request->path();
        if(Session::has('app_permissions')){
            $app_permissions = collect(Session::get('app_permissions'));
        }else{
            $app_permissions = collect(user_permission::get());
            Session::put('app_permissions', $app_permissions);
        }
        //if user is not logged in or does not have permissions
        if(!Session::has('user') || !Session::has('permissions')){
            if($action == 'GET'){
                return redirect('/admin');
            }else{
                return \Response::json([
                    'status' => 'error',
                    'message' => 'No tiene permisos para realizar esta acción ('.$path.')'
                ], 400);
            }
        }else{
            $admin = Session::get('user');
            if(
                $admin['reset_password'] != null
                && $request->path() != 'admin/reset-password'
                ){
                return redirect('/admin/reset-password');
            }
        }
        $user_permissions = collect(Session::get('permissions'));
        $current_url = $request->path();
        if(
            $current_url != 'admin'
            && strpos($current_url, 'admin/my-profile') === false
            && strpos($current_url, 'admin/users/close-session') === false
            && strpos($current_url, 'admin/pusher') === false
            && strpos($current_url, 'admin/instagram') === false
            && strpos($current_url, 'admin/facebook') === false
            && strpos($current_url, 'admin/blog') === false
            && strpos($current_url, 'admin/linkedin') === false
            && strpos($current_url, 'admin/twitter') === false
            && strpos($current_url, 'admin/freepik') === false
        ){
            $current_url = explode('/',$current_url[strlen($current_url) - 1] == '/' ? $current_url.substr(0, strlen($current_url) - 1) : $current_url);
            $current_url = array_filter($current_url, function ($value) {
                return !empty($value);
            });
            //$match_app_permissions = $app_permissions->where('url', $current_url);
            $match_app_permissions = $app_permissions->filter(function ($perm) use ($current_url) {
                $val = explode('/',$perm['url']);
                $val = array_filter($val, function ($value) {
                    return !empty($value);
                });
                return empty(array_diff($val, $current_url));
            });
            //if user is logged in but does not have permissions
            if($match_app_permissions->count() == 0){
                return redirect('/admin');
            }
            //if user is logged in and has permissions
            $match_permission = $user_permissions->where('user_permission_id', $match_app_permissions->first()['id']);
            if($match_permission->count() == 0){
                return redirect('admin/my-profile');
            }
            ////////////////////////////////////////////
        }
        ////////////////////////////////////
        //Add user traceability
        $action = $request->method();
        $path = $request->path();
        if($action == 'POST' && $path != 'admin/users/traceability'){
            $payload = collect($request->all())->filter(function ($value, $key) {
                return $value!=null;
            });
            $payload = json_encode($payload->except(['_token','password','password_confirmation', 'file', 'image', 'image_file', 'image_file_name', 'image_file_type', 'image_file_size', 'image_file_tmp_name', 'image_file_error']));
            //if $payload is empty
            if($payload == '[]'){
                $payload = null;
            }
            if($payload != null){
                $user_traceability = new user_traceability();
                $user_traceability->unique_id = strtoupper(Str::uuid()->toString());
                $user_traceability->user_id = Session::get('user')['id'];
                $user_traceability->action = $request->method();
                $user_traceability->path = $request->path();
                $user_traceability->ip = $request->ip();
                $user_traceability->user_agent = $request->userAgent();
                $user_traceability->payload = $payload;
                $user_traceability->parameters = json_encode($request->route()->parameters());
                $user_traceability->save();
            }
            
        }
        ////////////////////////////////////
        return $next($request);
    }
}
