<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

use App\traits\client_users_trait;


class client_users_controller extends Controller
{
    //
    use client_users_trait;
    public function get_all_client_users(Request $request){
        $Response = $this->ClientUser_GetAllClientUsers();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_all_client_users_by_client_id(Request $request){
        $Response = $this->ClientUser_GetAllClientUsersByClientId(
            Session::get('client_user')['active_client']['id']
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_client_user(Request $request){
        $Response = $this->ClientUser_AddClientUser(
            $request->client_id
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->phone
            ,$request->position
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function session_add_client_user(Request $request){
        $Response = $this->ClientUser_AddClientUser(
            Session::get('client_user')['active_client']['id']
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->phone
            ,$request->position
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_client_user(Request $request){
        $Response = $this->ClientUser_UpdateClientUser(
            $request->id
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->phone
            ,$request->position
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function session_update_client_user(Request $request){
        $Response = $this->ClientUser_UpdateClientUser(
            $request->id
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->phone
            ,$request->position
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_my_profile(Request $request){
        $Response = $this->ClientUser_UpdateClientUser(
            Session::get('client_user')['id']
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->phone
            ,$request->position
            ,$request->color
            ,$request->password
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_client_user_profile(Request $request){
        $Response = $this->ClientUser_UpdateClientUserProfile(
            Session::get('client_user')['id']
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->phone
            ,$request->position
            ,$request->color
            ,$request->password
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_client_user_by_id(Request $request){
        $Response = $this->ClientUser_GetClientUserById(
            $request->client_user_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function next_id(Request $request){
        $Response = $this->ClientUser_NextId();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function login_client_user(Request $request){
        $Response = $this->ClientUser_LoginClientUser(
            $request->identification
            ,$request->password
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function close_session(Request $request){
        $Response = $this->ClientUser_CloseSession();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_client_users_page(Request $request){
        $Response = $this->ClientUser_GetPage(
            Session::get('client_user')['active_client']['id'],
            $request->pagination,
            [Session::get('client_user')['id']],
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_client_users_by_client_id(Request $request){
        $Response = $this->ClientUser_GetClientUsersByClientId(
            $request->client_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function restore_client_user_password(Request $request){
        $Response = $this->ClientUser_RestoreClientUserPassword(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function forgot_password(Request $request){
        $Response = $this->ClientUser_ForgotPassword(
            $request->email
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_client_user(Request $request){
        $Response = $this->ClientUser_DeleteClientUser(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function restore_client_user(Request $request){
        $Response = $this->ClientUser_RestoreClientUser(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function set_client_user_password(Request $request){
        $Response = $this->ClientUser_SetClientUserPassword(
            Session::get('client_user')['id']
            ,$request->password
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //ClientUser permissions
    public function get_all_permissions(Request $request){
        $Response = $this->ClientUserPermissions_GetAllPermissions();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_permissions_by_user(Request $request){
        $Response = $this->ClientUserPermissions_GetPermissionsByUserId(
            $request->client_user_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function client_users_get_permissions(Request $request){
        $Response = $this->ClientUserPermissions_GetPermissions();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function client_users_get_traceability(Request $request){
        $Response = $this->ClientUserPermissions_GetTraceability(
            $request->pagination,
            $request->client_user_id,
            $request->search,
            $request->date_from,
            $request->date_to,
            $request->url,
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
