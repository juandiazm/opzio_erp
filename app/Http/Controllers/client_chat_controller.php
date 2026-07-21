<?php

namespace App\Http\Controllers;

use Session;

use Illuminate\Http\Request;
use App\traits\client_chat_trait;
use App\traits\open_ia_trait;
use App\traits\mail_trait;

class client_chat_controller extends Controller
{
    use client_chat_trait
    , open_ia_trait
    ,mail_trait;
    public function get_client_messages_by_client(Request $request){
        $Response = $this->ClientChat_GetClientMessages(null, $request->_token, $request->stored_token, false, $request->APP_LANG);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function add_client_message(Request $request){
        $Response = $this->ClientChat_AddClientMessage(null, $request->_token, $request->message, $request->APP_LANG);
        if($Response['status']==1){
            if($Response['first_message']==true){
                $Mails = [];
                $Mails[] = 
                [
                    'address' => 'info@opzio.co',
                    'name' => 'Opzio Comunicación',
                ];
                $Mails[] = [
                    'address' => 'analista.tech@opzio.co',
                    'name' => 'Yuli Garzón'
                ];
                $Mails[] = [
                    'address' => 'mariaf.franco@opzio.co',
                    'name' => 'Maria Franco'
                ];
                $Mails[] = [
                    'address' => 'juandiazm@opzio.co',
                    'name' => 'Juan Diaz'
                ];
                $MailData = 
                [
                    'subject' => 'Página web - Chat',
                ];
                $View = 'mail.chat_request';
                $ViewData = collect(
                [
                    'chat' => $Response['chat'],
                    'chat_messages' => $Response['chat_message_history']
                ]
                );
                $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            }
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function add_client_message_as_admin(Request $request){
        $Response = $this->ClientChat_AddClientMessageAsAdmin($request->chat_id, Session::get('user')['id'], $request->message);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function save_new_thread_messages(Request $request){
        $Response = $this->ClientChat_SaveNewThreadMessages(null, $request->_token);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function get_client_chats(Request $request){
        $Response = $this->ClientChat_GetClientChats(null);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function get_client_chats_page(Request $request){
        $Response = $this->ClientChat_GetClientChats($request->pagination);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function get_client_messages_by_client_id(Request $request){
        $Response = $this->ClientChat_GetClientChatMessages($request->chat_id);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
    public function change_ia_response(Request $request){
        $Response = $this->ChangeIAResponse($request->chat_id, $request->ia_response);
        if($Response['status']==1){
            return $Response;
        }else{
            return \Response::json($Response , 400);
        }
    }
}
