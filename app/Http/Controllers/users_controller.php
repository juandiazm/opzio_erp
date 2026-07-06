<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

use App\traits\users_trait;

class users_controller extends Controller
{
    //
    use users_trait;
    public function get_all_users(Request $request){
        $Response = $this->User_GetAllUsers();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_user(Request $request){
        $Response = $this->User_AddUser(
            $request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->identification
            ,$request->password
            ,$request->photo
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_user(Request $request){
        $Response = $this->User_UpdateUser(
            $request->id
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->identification
            ,$request->password
            ,$request->photo
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_my_profile(Request $request){
        $Response = $this->User_UpdateUser(
            Session::get('user')['id']
            ,$request->name
            ,$request->lastname
            ,$request->username
            ,$request->email
            ,$request->identification
            ,$request->password
            ,$request->photo
            ,$request->color
            ,$request->permissions
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_user_by_id(Request $request){
        $Response = $this->User_GetUserById(
            $request->user_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function next_id(Request $request){
        $Response = $this->User_NextId();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function login_user(Request $request){
        $Response = $this->User_LoginUser(
            $request->identification
            ,$request->password
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function close_session(Request $request){
        $Response = $this->User_CloseSession();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_page(Request $request){
        $Response = $this->User_GetPage(
            $request->pagination
            ,$request->search
            ,true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_user(Request $request){
        $Response = $this->User_DeleteUser(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function restore_user(Request $request){
        $Response = $this->User_RestoreUser(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function forgot_password(Request $request){
        $Response = $this->User_ForgotPassword(
            $request->identification
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //User permissions
    public function users_get_permissions(Request $request){
        $Response = $this->UserPermissions_GetPermissions();
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function users_get_traceability(Request $request){
        $Response = $this->UserPermissions_GetTraceability(
            $request->pagination,
            $request->user_id,
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
    public function get_users_by_date_range_report(Request $request){
        $Response = $this->User_GetUsersByDateRangeReport(
            $request->fromDate,
            $request->toDate
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function reset_password(Request $request){
        $Response = $this->User_ResetPassword(
            $request->password
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
}
