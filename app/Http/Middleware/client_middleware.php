<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Str;


use App\Models\client_user_permission;
use App\Models\client_user_traceability;

class client_middleware
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
        $current_url = $request->path();
        if( strpos($current_url, 'client/payments/webhook') !== false){
            return $next($request);
        }
        if(Session::has('client_app_permissions')){
            $client_app_permissions = collect(Session::get('client_app_permissions'));
        }else{
            $client_app_permissions = collect(client_user_permission::get());
            Session::put('client_app_permissions', $client_app_permissions);
        }
        //if client_user is not logged in or does not have permissions
        if(!Session::has('client_user') || !Session::has('permissions')){
            return redirect('/');
        }
        $client_user_permissions = collect(Session::get('permissions'));
        
        if(
            $current_url != 'client'
            && strpos($current_url, 'client/profile') === false
            && strpos($current_url, 'client/dashboard') === false
            && strpos($current_url, 'client/getters') === false
        ){
            $current_url = explode('/',$current_url[strlen($current_url) - 1] == '/' ? $current_url.substr(0, strlen($current_url) - 1) : $current_url);
            $current_url = array_filter($current_url, function ($value) {
                return !empty($value);
            });
            $match_client_app_permissions = $client_app_permissions->where('url', $current_url);
            $match_client_app_permissions = $client_app_permissions->filter(function ($perm) use ($current_url) {
                $val = explode('/',$perm['url']);
                $val = array_filter($val, function ($value) {
                    return !empty($value);
                });
                return empty(array_diff($val, $current_url));
            });
            //if client_user is logged in but does not have permissions
            if($match_client_app_permissions->count() == 0){
                return redirect('client/dashboard');
            }
            //if client_user is logged in and has permissions
            $match_permission = $client_user_permissions->where('client_user_permission_id', $match_client_app_permissions->first()['id']);
            if($match_permission->count() == 0){
                return redirect('client/dashboard');
            }
            ////////////////////////////////////////////
        }
        ////////////////////////////////////
        //Add client_user traceability
        $action = $request->method();
        $path = $request->path();
        if($action == 'POST' && $path != 'client/client_users/traceability'){
            $payload = collect($request->all())->filter(function ($value, $key) {
                return $value!=null;
            });
            $payload = json_encode($payload->except(['_token','password','password_confirmation', 'file', 'image', 'image_file', 'image_file_name', 'image_file_type', 'image_file_size', 'image_file_tmp_name', 'image_file_error']));
            //if $payload is empty
            if($payload == '[]'){
                $payload = null;
            }
            if($payload != null){
                $client_user_traceability = new client_user_traceability();
                $client_user_traceability->unique_id = strtoupper(Str::uuid()->toString());
                $client_user_traceability->client_user_id = Session::get('client_user')['id'];
                $client_user_traceability->action = $request->method();
                $client_user_traceability->path = $request->path();
                $client_user_traceability->ip = $request->ip();
                $client_user_traceability->user_agent = $request->userAgent();
                $client_user_traceability->payload = $payload;
                $client_user_traceability->parameters = json_encode($request->route()->parameters());
                $client_user_traceability->save();
            }
            
        }
        ////////////////////////////////////
        return $next($request);
    }
}
