<?php 
namespace App\traits;

use App;

use Carbon\Carbon;

use App\Models\client_chat;
use App\Models\client_chat_message;

#REGLOG 50
trait client_chat_trait
{
    use twilio_sms_trait;
    private function ClientChat_GetClientMessages(
        $client_id
        , $token
        , $stored_token
        , $is_admin_reader
        , $APP_LANG = 'es'
        
        ) {
        try{
            $run_assistant_data = null;
            if($client_id != null){
                $chat = client_chat::where('id', $client_id)->first();
            }else if($stored_token != null){
                $chat = client_chat::where('token', $stored_token)->first();
                if($chat && $chat->token != $token){
                    $chat->token = $token;
                    $chat->save();
                }
            }else{
                $stored_token = $token;
                $chat = client_chat::where('token', $token)->first();
            }
            if(!$chat){
                $chat = new client_chat();
                $chat->token = $token;
                $chat->client_id = $client_id;
            }
            if($chat->assistant_id == null){
                if($APP_LANG == 'es'){
                    $chat->assistant_id = $this->ES_ASSISTANT_ID;
                }else{
                    $chat->assistant_id = $this->ENG_ASSISTANT_ID;
                }
            }
            if($chat->thread_id == null){
                $thread = $this->OpenIA_AddThread();
                if($thread['status']==1){
                    $chat->thread_id = $thread['data']['thread_id'];
                }
            }
            $chat->save();
            $messages = client_chat_message::where('client_chat_id', $chat->id)->orderBy('id')->get();
            if(count($messages)==0){
                if($chat->thread_id != null){
                    $Response = $this->OpenIA_RunAssistant($chat->assistant_id, $chat->thread_id);
                    if($Response['status']==1){
                        $run_assistant_data = $Response['data'];
                    }
                }
                if($run_assistant_data == null){
                    $message = new client_chat_message();
                    $message->client_chat_id = $chat->id;
                    $message->is_admin = 1;
                    $message->message = 'Hola, soy Maya, el asistente virtual de RIDDER, en que te puedo ayudar?';
                    $message->save();
                }
                $messages = client_chat_message::where('client_chat_id', $chat->id)->orderBy('id')->get();
            }
            if($is_admin_reader == true){
                $not_readed = $messages->where('is_read', 0)->where('is_admin', 0);
                foreach($not_readed as $message){
                    $message->is_read = 1;
                    $message->save();
                }
                
            }else{
                $not_readed = $messages->where('is_read', 0)->where('is_admin', 1);
                foreach($not_readed as $message){
                    $message->is_read = 1;
                    $message->save();
                }
            }
            return [
                'status' => 1,
                'message' => 'Success',
                'messages' => $messages,
                'not_read' => $not_readed->count(),
                'run_assistant_data' => $run_assistant_data,
                'client_chat_token' => $chat->token
            ];
        }catch(\Exception $e){
            info('ClientChat_GetClientMessages error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientChat_SaveNewConversationMessages($chat_id, $ia_messages){
        try{
            $current_messages = client_chat_message::where('client_chat_id', $chat_id)->get();
            $current_messages_ids = $current_messages->pluck('message_id')->toArray();
            $ia_messages_ids = collect($ia_messages)->pluck('id')->toArray();
            //get all ia_messages not in current_messages by message_id
            $new_messages = collect($ia_messages)->whereNotIn('id', $current_messages_ids);
            //reverse the order of the array
            $new_messages = $new_messages->reverse();
            $saved_messages = [];
            foreach($new_messages as $message){
                $chat_message = new client_chat_message();
                $chat_message->client_chat_id = $chat_id;
                $chat_message->is_admin = $message->role == 'assistant' ? 1 : 0;
                $chat_message->message = collect(collect(collect($message->content)[0])["text"])["value"];
                $chat_message->message_id = $message->id;
                $chat_message->save();
                $saved_messages[] = $chat_message;
                if(App::environment() === 'production'){
                    event(new \App\Events\pusherEvents('chat','chat',$chat_message));
                }
            }
            $chat = client_chat::where('id', $chat_id)->first();
            if($chat){
                $chat->updated_at = Carbon::now();
                $chat->save();
            }
            return [
                'status' => 1,
                'message' => 'Success',
                'messages' => $saved_messages
            ];
        }catch(\Exception $e){
            info('ClientChat_SaveNewConversationMessages error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientChat_AddClientMessage(
        $client_id
        , $token
        , $message_input
        , $APP_LANG = 'es'
        ) {
        $run_assistant_data = null;
        $first_message = false;
        $chat_messages = [];
        try{
            if($client_id != null){
                $chat = client_chat::where('id', $client_id)->first();
            }else{
                $chat = client_chat::where('token', $token)->first();
            }
            if(!$chat){
                $chat = new client_chat();
                $chat->token = $token;
                $chat->client_id = $client_id;
                $chat->save();
                
            }else{
                $chat->updated_at = Carbon::now();
            }
            if($chat->assistant_id == null){
                if($APP_LANG == 'es'){
                    $chat->assistant_id = $this->ES_ASSISTANT_ID;
                }else{
                    $chat->assistant_id = $this->ENG_ASSISTANT_ID;
                }
            }
            if($chat->thread_id == null){
                $thread = $this->OpenIA_AddThread();
                if($thread['status']==1){
                    $chat->thread_id = $thread['data']['thread_id'];
                }
            }
            $chat->save();
            if(!$chat->has_email){
                $words = explode(' ', $message_input);
                $email = '';
                //Search the firs email in the message
                foreach($words as $word){
                    if(filter_var($word, FILTER_VALIDATE_EMAIL)){
                        $email = $word;
                        break;
                    }
                }
                if($email != ''){
                    $chat->has_email = 1;
                    $chat->client_email = $email;
                    $chat->save();
                }
            }
            if($chat->ia_response == 1){
                if($APP_LANG == 'es'){
                    $CHAT_RESTRICTION = '. Responde en español y responde con menos de 300 caracteres obligatoriamente';
                }else{
                    $CHAT_RESTRICTION = '. Answer in english and answer with less than 300 characters obligatorily';
                }
                $Response = $this->OpenIA_AddMessage($chat->thread_id, 'user', $message_input.$CHAT_RESTRICTION);
                if($Response['status'] == 1){
                    $message = new client_chat_message();
                    $message->client_chat_id = $chat->id;
                    $message->is_admin = 0;
                    $message->message = $message_input;
                    $message->message_id = $Response['data']['id'];
                    $message->save();
                    
                    $Response = $this->OpenIA_RunAssistant($chat->assistant_id, $chat->thread_id);
                    if($Response['status']==1){
                        $run_assistant_data = $Response['data'];
                    }
                    if(App::environment() === 'production'){
                        event(new \App\Events\pusherEvents('chat','chat',$message));
                    }
                    if(client_chat_message::where('client_chat_id', $chat->id)->where('is_admin', 0)->count() == 1){
                        $this->TwilioSMS_SendMessage(
                            '+57',
                            '3108226480', //JUAN
                            'Nuevo mensaje en el chat de la página web '.$message_input
                        );
                        $this->TwilioSMS_SendMessage(
                            '+57',
                            '3112135202', //MAFE
                            'Nuevo mensaje en el chat de la página web '.$message_input
                        );
                        $this->TwilioSMS_SendMessage(
                            '+57',
                            '3107768924', //YULI
                            'Nuevo mensaje en el chat de la página web: '.$message_input
                        );
                        $first_message = true;
                    }
                    return [
                        'status' => 1,
                        'message' => 'Success',
                        'chat' => $chat	,
                        'run_assistant_data' => $run_assistant_data,
                        'first_message' => $first_message,
                        'chat_messages' => $chat_messages,
                        'chat_message_history' => client_chat_message::where('client_chat_id', $chat->id)->orderBy('id')->get()
                    ];
                }
            }else{
                $message = new client_chat_message();
                $message->client_chat_id = $chat->id;
                $message->is_admin = 0;
                $message->message = $message_input;
                $message->message_id = null;
                $message->save();
                $chat_messages = client_chat_message::where('client_chat_id', $chat->id)->orderBy('id')->get();
                if(App::environment() === 'production'){
                    event(new \App\Events\pusherEvents('chat','chat',$message));
                }
                return [
                    'status' => 1,
                    'message' => 'Success',
                    'chat' => $chat,
                    'run_assistant_data' => $run_assistant_data,
                    'first_message' => $first_message,
                    'chat_messages' => $chat_messages
                ];
            }
            
            return $Response;
            
        }catch(\Exception $e){
            info('ClientChat_AddClientMessage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
                'chat' => null,
                'run_assistant_data' => $run_assistant_data,
                'first_message' => $first_message
            ];
        }
    }
    public function ClientChat_AddClientMessageAsAdmin($chat_id, $admin_id, $message_input){
        try{
            $chat = client_chat::where('id', $chat_id)->first();
            if(!$chat){
                return [
                    'status' => 0,
                    'message' => 'Chat not found'
                ];
            }
            $message = new client_chat_message();
            $message->client_chat_id = $chat->id;
            $message->is_admin = 1;
            $message->message = $message_input;
            $message->admin_id = $admin_id;
            $message->save();
            $chat->updated_at = Carbon::now();
            $chat->save();
            if(App::environment() === 'production'){
                event(new \App\Events\pusherEvents('chat-'.$chat->token,'chat',$message));
            }
            return [
                'status' => 1,
                'message' => 'Success',
                'data' => $message
            ];
            
            return $Response;
        }catch(\Exception $e){
            info('ClientChat_AddClientMessageAsAdmin error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientChat_SaveNewThreadMessages($client_id, $token, $is_admin_reader = false){
        $Response = [
            'status' => 1,
            'message' => '',
            'messages' => []
        ];
        try{
            $messages = [];
            if($client_id != null){
                $chat = client_chat::where('id', $client_id)->first();
            }else{
                $chat = client_chat::where('token', $token)->first();
            }
            if(!$chat){
                return [
                    'status' => 0,
                    'message' => 'Chat not found'
                ];
            }
            $Response = $this->OpenIA_GetMessages($chat->thread_id);
            if($Response['status']==1){
                $Response = $this->ClientChat_SaveNewConversationMessages($chat->id, $Response['data']['data']);
                if($Response['status'] == 1){
                    return  $this->ClientChat_GetClientMessages($client_id, $token,$token, $is_admin_reader);
                }
            }
            
        }catch(\Exception $e){
            info('ClientChat_SaveNewThreadMessages error: '.$e->getMessage());
            $Response['status'] = 0;
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function ClientChat_GetClientChats(
        $pagination = null
    ){
        try{
            $chats = client_chat::with(['last_message'])
            ->withCount(['messages as messages_count' => function($query) {
                $query->where('is_admin', 0);
            }])
            ->orderBy('updated_at', 'desc')
            ->having('messages_count', '>', 0);

            if($pagination != null){
                $pagination['total'] = $chats->count();
                $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
                $chats = $chats->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page']);
            }
            return [
                'status' => 1,
                'message' => 'Success',
                'data' => $chats->get(),
                'pagination' => $pagination
            ];
        }catch(\Exception $e){
            info('ClientChat_GetClientChats error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ClientChat_GetClientChatMessages($chat_id){
        try{
            $messages = client_chat_message::where('client_chat_id', $chat_id)->orderBy('id')->get();
            return [
                'status' => 1,
                'message' => 'Success',
                'data' => $messages
            ];
        }catch(\Exception $e){
            info('ClientChat_GetClientChatMessages error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function ChangeIAResponse($chat_id, $ia_response){
        try{
            $chat = client_chat::where('id', $chat_id)->first();
            if(!$chat){
                return [
                    'status' => 0,
                    'message' => 'Chat not found'
                ];
            }
            $chat->ia_response = $ia_response;
            $chat->save();
            return [
                'status' => 1,
                'message' => 'Success',
                'data' => $chat
            ];
        }catch(\Exception $e){
            info('ChangeIAResponse error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}
