<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;
use Session;
use OpenAI\Laravel\Facades\OpenAI;

use App\Models\open_ia_assistant;
use App\Models\open_ia_thread;
use App\Models\open_ia_run_register;

trait open_ia_trait
{
	private $OpenIAClient = null;
	public $ENG_ASSISTANT_ID = 'asst_tQnGJMRH6rKOOedMw5Q87Tic';
	public $ES_ASSISTANT_ID = 'asst_RHAJcpImaR0bvnG9dS8feSn7';
	public $ES_BLOG_ASSISTANT_ID = 'asst_rR1hP0mydJYiVkvTs4Ta0BVN';
	public $ES_INSTAGRAM_ASSISTANT_ID = 'asst_HTJPYFVC3vOlG8RTfjcDiGGC';
	public $ES_FACEBOOK_ASSISTANT_ID = 'asst_rR1hP0mydJYiVkvTs4Ta0BVN';
	public $ES_LINKEDIN_ASSISTANT_ID = 'asst_whJGy5hRiEpnR8T8a14890hZ';
	public function OpenIA_GetConnection(){
		$this->OpenIAClient = new Client([
			'base_uri' => 'https://api.openai.com/v1/',
			'headers' => [
				'Authorization' => 'Bearer ' . env('CHATGPT_API_KEY'),
				'Content-Type' => 'application/json',
			],
			'verify' => false,
			'timeout' => 240,
		]);
		return '1';
	}
	public function OpenIA_PostRequest($url, $SendData, $headers = null){
		try{
			if($this->OpenIAClient == null){
				$this->OpenIA_GetConnection();
			}
			$myBody = [];
			if($SendData != null){
				foreach ($SendData as $key => $value) {
					$myBody[$key] = $value;
				}
			}
			if($headers == null){
				$response = $this->OpenIAClient->post($url, [
					'json' => $myBody
				])->getBody()->getContents();
			}else{
				$response = $this->OpenIAClient->post($url, [
					'headers' => $headers,
					'json' => $myBody
				])->getBody()->getContents();
			}
			//$response = $this->Client->post($url, ['json'=>$myBody])->getBody()->getContents();
			if($response =='fail'){
				$response = collect();
			}
			return [
				'status' => 1,
				'message' => 'success',
				'data' => collect(json_decode($response)),
			];
		}catch(\GuzzleHttp\Exception\RequestException $e){
			info('OpenIA_PostRequest '.$e->getMessage());
			return [
				'status' => 0,
				'messsage' => $e->getMessage()
			];
		}
	}
	public function OpenIA_GetRequest($url, $header = null){
		try{
			if($this->OpenIAClient == null){
				$this->OpenIA_GetConnection();
			}
			if($header == null){
				$response = $this->OpenIAClient->get($url)->getBody()->getContents();
			}else{
				$response = $this->OpenIAClient->get($url, [
					'headers' => $header
				])->getBody()->getContents();
			}
			//$response = $this->Client->post($url, ['json'=>$myBody])->getBody()->getContents();
			if($response =='fail'){
				$response = collect();
			}
			return [
				'status' => 1,
				'message' => 'success',
				'data' => collect(json_decode($response)),
			];
		}catch(\GuzzleHttp\Exception\RequestException $e){
			info('OpenIA_GetRequest '.$e->getMessage());
			return [
				'status' => 0,
				'messsage' => $e->getMessage()
			];
		}
	
	}
	public function OpenIA_MakeQuestion($message){
		$SendData =[
			'model' => 'gpt-4.1-nano',
			'messages' => [
				['role' => 'system', 'content' => 'You are'],
				['role' => 'user', 'content' => $message],
			],
		];
		$response = $this->OpenIA_PostRequest('chat/completions', $SendData);
		if($response['status']==1){
			$result = [];
			foreach ($response['data']['choices'] as $value) {
				$result[] = collect(collect($value)['message'])['content'];
			}
			$response['data'] = $result;
		}
		return $response;
	}
	public function OpenIA_AddAssistant($instruction, $name, $tools = [["type" => "retrieval"]], $model = "gpt-4o-mini"){
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try{
			$SendData =[
				"instructions" => $instruction,
				"name" => $name,
				"tools" => $tools,
				"model" => $model
			];
			$headers = [
				'OpenAI-Beta' => 'assistants=v2',
			];
			$Response = $this->OpenIA_PostRequest('assistants', $SendData, $headers);
			if($Response['status']==1){
				$data = $Response['data'];
				$assistant = new open_ia_assistant();
				$assistant->assistant_id = $data['id'];
				$assistant->object = $data['object'];
				$assistant->createdAt = $data['created_at'];
				$assistant->name = $data['name'];
				$assistant->tools = json_encode($data['tools']);
				$assistant->model = json_encode($data['model']);
				$assistant->instructions = $data['instructions'];
				$assistant->save();
				$Response['data'] = $assistant;
			}
		}catch(\Exception $e){
			info('OpenIA_AddAssistant error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		
		return $Response;
	}
	public function OpenIA_AddThread(){
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try{
			$SendData =[];
			$headers = [
				'OpenAI-Beta' => 'assistants=v2',
			];
			$Response = $this->OpenIA_PostRequest('threads', $SendData, $headers);
			if($Response['status']==1){
				$data = $Response['data'];
				$thread = new open_ia_thread();
				$thread->thread_id = $data['id'];
				$thread->object = $data['object'];
				$thread->createdAt = $data['created_at'];
				$thread->save();
				$Response['data'] = $thread;
			}
		}catch(\Exception $e){
			info('OpenIA_AddThread error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		
		return $Response;
	}
	public function OpenIA_GetMessages($thread_id){
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try{
			$SendData =[];
			$headers = [
				'OpenAI-Beta' => 'assistants=v2',
			];
			$Response = $this->OpenIA_GetRequest('threads/'.$thread_id.'/messages', $headers);
			if($Response['status']==1){
				$data = $Response['data'];
				$Response['data'] = $data;
			}
		}catch(\Exception $e){
			info('OpenIA_GetMessages error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		
		return $Response;
	}
	public function OpenIA_AddMessage($thread_id, $role, $content){
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try{
			$SendData =[
				'role' => $role,
				'content' => $content
			];
			$headers = [
				'OpenAI-Beta' => 'assistants=v2',
			];
			$ResponseRequest = $this->OpenIA_PostRequest('threads/'.$thread_id.'/messages', $SendData, $headers);
			if($ResponseRequest['status']==1){
				$Response['data'] = $ResponseRequest['data'];
				$Response['status'] = 1;
			}
		}catch(\Exception $e){
			info('OpenIA_AddMessage error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		
		return $Response;
	}
	public function OpenIA_RunAssistant($assistant_id, $thread_id){
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try{
			$SendData =[
				'assistant_id' => $assistant_id
			];
			$headers = [
				'OpenAI-Beta' => 'assistants=v2',
			];
			$Response = $this->OpenIA_PostRequest('threads/'.$thread_id.'/runs', $SendData, $headers);
			if($Response['status']==1){
				$data = $Response['data'];
				$run_register = new open_ia_run_register();
				$run_register->run_id = $data['id'];
				$run_register->object = $data['object'];
				$run_register->createdAt = $data['created_at'];
				$run_register->assistant_id = $data['assistant_id'];
				$run_register->thread_id = $data['thread_id'];
				$run_register->status_string = $data['status'];
				$run_register->status = 0;
				$run_register->save();
				$Response['data'] = $data;
			}
		}catch(\Exception $e){
			info('OpenIA_RunAssistant error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		
		return $Response;
	}
	public function OpenIA_GetRunAssistant($thread_id, $run_id){
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try{
			$SendData =[];
			$headers = [
				'OpenAI-Beta' => 'assistants=v2',
			];
			$Response = $this->OpenIA_GetRequest('threads/'.$thread_id.'/runs/'.$run_id, $headers);
			if($Response['status']==1){
				$data = $Response['data'];
				//check if status is diferent to queued
				if($data['status'] != 'queued'){
					$run_register = open_ia_run_register::where('run_id', $run_id)->first();
					if($run_register != null){
						$run_register->status_string = $data['status'];
						$run_register->status = 1;
						$run_register->save();
					}
					
				}
				$Response['data'] = [
					'id' => $data['id'],
					'status' => $data['status'],
				];
			}
		}catch(\Exception $e){
			info('OpenIA_GetRunAssistant error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		
		return $Response;
	}
	public function OpenIA_MakeQuestionToAssistant(
		$assistant_id
		, $thread_id
		, $message
		, $max_tries = 3
		, $waiting_time = 1
	){
		$Response = [
			'status' => 0,
			'message' => '',
			'data' => []
		];
		try{
			$FunctionResponse = $this->OpenIA_AddMessage($thread_id, 'user', $message);
            if($FunctionResponse['status'] == 1){
				$FunctionResponse = $this->OpenIA_RunAssistant($assistant_id, $thread_id);
				if($FunctionResponse['status']==1){
					$run_assistant_data = $FunctionResponse['data'];
					$response_getted = false;
					$current_try = 0;
					while(!$response_getted && $current_try<$max_tries){
						sleep($waiting_time);
                        $current_try++;
                        $FunctionResponse = $this->OpenIA_GetMessages($thread_id);
                        if($FunctionResponse['status']==1){
                            /*Get the title of the blog*/
                            $message_response = $FunctionResponse['data']['data'];
                            foreach($message_response as $message){
                                $message = collect($message);
                                if($message['role']=='assistant' && $message['run_id'] == $run_assistant_data['id'] && count($message['content'])>0){
                                    foreach($message['content'] as $content){
                                        $msj = collect(collect($content)['text'])['value'];
                                        $Response['data'][] = str_replace('"', '', $msj);
                                    }
                                    $response_getted = true;
                                    break;
                                }
                            }
                        }
                    }
					if($response_getted == true){
						$Response['status'] = 1;
					}else{
						$Response['message'] = 'No response from assistant';
					}
				}else{
					$Response['message'] = $FunctionResponse['message'];
				}
			}else{
				$Response['message'] = $FunctionResponse['message'];
			}
		}catch(\Exception $e){
			info('OpenIA_MakeQuestionToAssistant error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function OpenIA_GenerateImage($message){
		$Response = [
			'status' => 0,
			'message' => '',
			'data' => []
		];
		try{
			$SendData =[
				'model' => 'dall-e-3',
				'prompt' => $message,
				"n" => 1,
				"size" => "1024x1024",
			];
			$headers = [
				'OpenAI-Beta' => 'images=v1',
			];
			$Response = $this->OpenIA_PostRequest('images/generations', $SendData, $headers);
			if($Response['status']==1){
				$data = $Response['data'];
				$Response['data'] = $data;
			}
		}catch(\Exception $e){
			info('OpenIA_GenerateImage error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
}