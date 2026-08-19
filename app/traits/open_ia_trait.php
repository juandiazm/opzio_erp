<?php

namespace App\traits;

use App\Models\open_ia_assistant;
use App\Models\open_ia_run_register;
use App\Models\open_ia_thread;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait open_ia_trait
{
	private $OpenIAClient = null;
	private $OpenIAConversationAliases = [];
	private const OPENIA_BASE_URI = 'https://api.openai.com/v1/';
	private const OPENIA_DEFAULT_MODELS = [
		'fast' => 'gpt-5.6-luna',
		'chat' => 'gpt-5.6-terra',
		'content' => 'gpt-5.6-terra',
		'reasoning' => 'gpt-5.6-sol',
		'image' => 'gpt-image-2',
	];
	public $ENG_ASSISTANT_ID = 'asst_tQnGJMRH6rKOOedMw5Q87Tic';
	public $ES_ASSISTANT_ID = 'asst_RHAJcpImaR0bvnG9dS8feSn7';
	public $ES_BLOG_ASSISTANT_ID = 'asst_rR1hP0mydJYiVkvTs4Ta0BVN';
	public $ES_INSTAGRAM_ASSISTANT_ID = 'asst_HTJPYFVC3vOlG8RTfjcDiGGC';
	public $ES_FACEBOOK_ASSISTANT_ID = 'asst_rR1hP0mydJYiVkvTs4Ta0BVN';
	public $ES_LINKEDIN_ASSISTANT_ID = 'asst_whJGy5hRiEpnR8T8a14890hZ';

	public function OpenIA_GetModel($purpose = 'fast')
	{
		$purpose = array_key_exists($purpose, self::OPENIA_DEFAULT_MODELS) ? $purpose : 'fast';
		$services = config('services');
		$models = is_array($services) ? ($services['openai']['models'] ?? []) : [];

		return (string) ($models[$purpose] ?? self::OPENIA_DEFAULT_MODELS[$purpose]);
	}

	public function OpenIA_GetConnection(){
		$services = config('services');
		$openaiConfig = is_array($services) ? ($services['openai'] ?? []) : [];
		$apiKey = $openaiConfig['api_key'] ?? null;
		if(!$apiKey){
			$this->OpenIAClient = null;
			return '0';
		}

		$this->OpenIAClient = new Client([
			'base_uri' => self::OPENIA_BASE_URI,
			'headers' => [
				'Authorization' => 'Bearer ' . $apiKey,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			],
			'verify' => (bool) ($openaiConfig['verify_tls'] ?? true),
			'connect_timeout' => 10,
			'timeout' => (float) ($openaiConfig['timeout'] ?? 240),
			'http_errors' => false,
		]);
		return '1';
	}

	public function OpenIA_PostRequest($url, $SendData, $headers = null){
		return $this->OpenIA_ExposeLegacyResponse(
			$this->OpenIA_RequestJson('POST', $url, $SendData, $headers ?: [])
		);
	}

	public function OpenIA_GetRequest($url, $header = null){
		return $this->OpenIA_ExposeLegacyResponse(
			$this->OpenIA_RequestJson('GET', $url, null, $header ?: [])
		);
	}

	public function OpenIA_MakeQuestion($message, $model = null, $options = [])
	{
		$options = is_array($options) ? $options : [];
		$payload = [
			'model' => $model ?: $this->OpenIA_GetModel($options['purpose'] ?? 'fast'),
			'instructions' => $options['instructions'] ?? 'You are a helpful assistant.',
			'input' => (string) $message,
			'store' => array_key_exists('store', $options) ? (bool) $options['store'] : false,
		];

		foreach(['max_output_tokens', 'temperature', 'top_p', 'reasoning_effort'] as $option){
			if(array_key_exists($option, $options)){
				if($option === 'reasoning_effort'){
					$payload['reasoning'] = ['effort' => $options[$option]];
				}else{
					$payload[$option] = $options[$option];
				}
			}
		}

		if(isset($options['json_schema']) && is_array($options['json_schema'])){
			$schema = $options['json_schema'];
			$payload['text'] = [
				'format' => [
					'type' => 'json_schema',
					'name' => $schema['name'] ?? 'structured_response',
					'strict' => $schema['strict'] ?? true,
					'schema' => $schema['schema'] ?? $schema,
				],
			];
		}

		$response = $this->OpenIA_RequestJson('POST', 'responses', $payload);
		if($response['status'] !== 1){
			return $response;
		}

		$texts = $this->OpenIA_ExtractResponseText($response['data']);
		if(count($texts) === 0){
			return [
				'status' => 0,
				'message' => 'OpenAI no devolvio texto.',
				'data' => [],
			];
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => $texts,
			'response_id' => $response['data']['id'] ?? null,
			'model' => $response['data']['model'] ?? $payload['model'],
			'usage' => $response['data']['usage'] ?? null,
		];
	}
	public function OpenIA_AddAssistant($instruction, $name, $tools = [["type" => "retrieval"]], $model = null)
	{
		$Response = [
			'status' => 0,
			'message' => '',
		];

		try{
			$assistant = new open_ia_assistant();
			$assistant->assistant_id = 'profile_'.Str::lower(Str::random(32));
			$assistant->object = 'local_profile';
			$assistant->createdAt = (string) time();
			$assistant->name = $name;
			$assistant->tools = json_encode($tools ?: []);
			$assistant->model = $model ?: $this->OpenIA_GetModel('content');
			$assistant->instructions = $instruction;
			$assistant->save();

			return [
				'status' => 1,
				'message' => 'Perfil de IA registrado correctamente.',
				'data' => $assistant,
			];
		}catch(\Throwable $e){
			info('OpenIA_AddAssistant error', ['message' => $e->getMessage()]);
			$Response['message'] = 'No se pudo registrar el perfil de IA.';
		}

		return $Response;
	}

	public function OpenIA_AddThread()
	{
		$response = $this->OpenIA_RequestJson('POST', 'conversations', []);
		if($response['status'] !== 1){
			return $response;
		}

		try{
			$data = $response['data'];
			$thread = new open_ia_thread();
			$thread->thread_id = $data['id'];
			$thread->object = $data['object'] ?? 'conversation';
			$thread->createdAt = (string) ($data['created_at'] ?? time());
			$thread->metadata = json_encode($data['metadata'] ?? []);
			$thread->save();

			return [
				'status' => 1,
				'message' => 'Conversacion creada correctamente.',
				'data' => $thread,
			];
		}catch(\Throwable $e){
			info('OpenIA_AddThread persistence error', ['message' => $e->getMessage()]);
			return [
				'status' => 0,
				'message' => 'La conversacion se creo en OpenAI, pero no se pudo registrar localmente.',
			];
		}
	}

	public function OpenIA_GetMessages($thread_id)
	{
		$conversationId = $this->OpenIA_ResolveConversationId($thread_id);
		if(!$conversationId){
			return [
				'status' => 0,
				'message' => 'No se pudo resolver la conversacion.',
			];
		}

		$response = $this->OpenIA_RequestJson(
			'GET',
			'conversations/'.$conversationId.'/items',
			null,
			[],
			['query' => ['order' => 'asc', 'limit' => 100]]
		);
		if($response['status'] !== 1){
			return $response;
		}

		$data = $response['data'];
		$messages = [];
		foreach(($data['data'] ?? []) as $item){
			$normalized = $this->OpenIA_NormalizeConversationItem($item);
			if($normalized !== null){
				$messages[] = $normalized;
			}
		}
		$data['data'] = $messages;

		return [
			'status' => 1,
			'message' => 'success',
			'data' => $data,
		];
	}

	public function OpenIA_AddMessage($thread_id, $role, $content)
	{
		$conversationId = $this->OpenIA_ResolveConversationId($thread_id);
		if(!$conversationId){
			return [
				'status' => 0,
				'message' => 'No se pudo resolver la conversacion.',
			];
		}

		$role = in_array($role, ['user', 'assistant', 'system', 'developer'], true) ? $role : 'user';
		$response = $this->OpenIA_RequestJson(
			'POST',
			'conversations/'.$conversationId.'/items',
			[
				'items' => [[
					'type' => 'message',
					'role' => $role,
					'content' => (string) $content,
				]],
			]
		);
		if($response['status'] !== 1){
			return $response;
		}

		$item = $response['data']['data'][0] ?? $response['data'];
		$normalized = $this->OpenIA_NormalizeConversationItem($item);
		if($normalized === null){
			$normalized = [
				'id' => $item['id'] ?? null,
				'role' => $role,
				'content' => [[
					'type' => 'text',
					'text' => ['value' => (string) $content],
				]],
			];
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => $normalized,
		];
	}

	public function OpenIA_RunAssistant($assistant_id, $thread_id, $input = null)
	{
		$conversationId = $this->OpenIA_ResolveConversationId($thread_id);
		if(!$conversationId){
			return [
				'status' => 0,
				'message' => 'No se pudo resolver la conversacion.',
			];
		}

		$profile = $this->OpenIA_AssistantProfile($assistant_id);
		$payload = [
			'model' => $profile['model'],
			'instructions' => $profile['instructions'],
			'conversation' => $conversationId,
			'store' => true,
		];
		if($input !== null && $input !== ''){
			$payload['input'] = (string) $input;
		}

		$response = $this->OpenIA_RequestJson('POST', 'responses', $payload);
		if($response['status'] !== 1){
			return $response;
		}

		$data = $response['data'];
		$data['conversation_id'] = $conversationId;
		$data['output_texts'] = $this->OpenIA_ExtractResponseText($data);

		try{
			$run_register = new open_ia_run_register();
			$run_register->run_id = $data['id'];
			$run_register->object = $data['object'] ?? 'response';
			$run_register->createdAt = (string) ($data['created_at'] ?? time());
			$run_register->assistant_id = $assistant_id;
			$run_register->thread_id = $conversationId;
			$run_register->status_string = $data['status'] ?? 'completed';
			$run_register->status = ($data['status'] ?? 'completed') === 'completed' ? 1 : 0;
			$run_register->save();
		}catch(\Throwable $e){
			info('OpenIA_RunAssistant persistence error', ['message' => $e->getMessage()]);
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => $data,
		];
	}

	public function OpenIA_GetRunAssistant($thread_id, $run_id)
	{
		if(str_starts_with((string) $run_id, 'run_')){
			$response = $this->OpenIA_RequestJson(
				'GET',
				'threads/'.$thread_id.'/runs/'.$run_id,
				null,
				['OpenAI-Beta' => 'assistants=v2']
			);
		}else{
			$response = $this->OpenIA_RequestJson('GET', 'responses/'.$run_id);
		}

		if($response['status'] !== 1){
			return $response;
		}

		$data = $response['data'];
		$status = $data['status'] ?? 'completed';
		try{
			$run_register = open_ia_run_register::where('run_id', $run_id)->first();
			if($run_register){
				$run_register->status_string = $status;
				$run_register->status = $status === 'completed' ? 1 : 0;
				$run_register->save();
			}
		}catch(\Throwable $e){
			info('OpenIA_GetRunAssistant persistence error', ['message' => $e->getMessage()]);
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => [
				'id' => $data['id'] ?? $run_id,
				'status' => $status,
				'output_texts' => $this->OpenIA_ExtractResponseText($data),
			],
		];
	}

	public function OpenIA_MakeQuestionToAssistant(
		$assistant_id
		, $thread_id
		, $message
		, $max_tries = 3
		, $waiting_time = 1
	){
		$addedMessage = $this->OpenIA_AddMessage($thread_id, 'user', $message);
		if(($addedMessage['status'] ?? 0) !== 1){
			return [
				'status' => 0,
				'message' => $addedMessage['message'] ?? 'No se pudo agregar el mensaje.',
				'data' => [],
			];
		}

		$response = $this->OpenIA_RunAssistant($assistant_id, $thread_id);
		if(($response['status'] ?? 0) !== 1){
			return [
				'status' => 0,
				'message' => $response['message'] ?? 'No se pudo generar la respuesta.',
				'data' => [],
			];
		}

		$texts = $response['data']['output_texts'] ?? [];
		if(count($texts) === 0){
			return [
				'status' => 0,
				'message' => 'OpenAI no devolvio texto.',
				'data' => [],
			];
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => array_map(fn ($text) => str_replace('"', '', $text), $texts),
			'response_id' => $response['data']['id'] ?? null,
		];
	}

	public function OpenIA_GenerateImage($message)
	{
		$services = config('services');
		$openaiConfig = is_array($services) ? ($services['openai'] ?? []) : [];
		$imageConfig = $openaiConfig['image'] ?? [];
		$format = $imageConfig['format'] ?? 'webp';
		$response = $this->OpenIA_RequestJson('POST', 'images/generations', [
			'model' => $this->OpenIA_GetModel('image'),
			'prompt' => (string) $message,
			'n' => 1,
			'size' => $imageConfig['size'] ?? '1024x1024',
			'quality' => $imageConfig['quality'] ?? 'low',
			'output_format' => $format,
			'output_compression' => (int) ($imageConfig['compression'] ?? 90),
		]);
		if($response['status'] !== 1){
			return $response;
		}

		$images = [];
		$mime = $format === 'jpeg' ? 'image/jpeg' : 'image/'.$format;
		foreach(($response['data']['data'] ?? []) as $image){
			if(isset($image['b64_json']) && !isset($image['url'])){
				$image['url'] = 'data:'.$mime.';base64,'.$image['b64_json'];
			}
			$images[] = $image;
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => [
				'created' => $response['data']['created'] ?? time(),
				'data' => $images,
			],
		];
	}

	private function OpenIA_RequestJson($method, $url, $body = null, $headers = [], $options = [])
	{
		if($this->OpenIAClient === null && $this->OpenIA_GetConnection() !== '1'){
			return [
				'status' => 0,
				'message' => 'La API de OpenAI no esta configurada.',
			];
		}

		$services = config('services');
		$openaiConfig = is_array($services) ? ($services['openai'] ?? []) : [];
		$attempts = max(1, min(4, ((int) ($openaiConfig['retries'] ?? 2)) + 1));
		$lastFailure = null;

		for($attempt = 0; $attempt < $attempts; $attempt++){
			try{
				$requestOptions = ['headers' => $headers];
				if(array_key_exists('query', $options)){
					$requestOptions['query'] = $options['query'];
				}
				if(array_key_exists('json', $options)){
					$requestOptions['json'] = $options['json'];
				}elseif($body !== null){
					$requestOptions['json'] = $body;
				}

				$httpResponse = $this->OpenIAClient->request($method, $url, $requestOptions);
				$statusCode = $httpResponse->getStatusCode();
				$rawBody = $httpResponse->getBody()->getContents();
				$decoded = json_decode($rawBody, true);
				if($statusCode >= 200 && $statusCode < 300 && is_array($decoded)){
					return [
						'status' => 1,
						'message' => 'success',
						'data' => $decoded,
					];
				}

				$error = is_array($decoded) ? ($decoded['error'] ?? []) : [];
				$message = is_array($error) ? ($error['message'] ?? 'OpenAI devolvio un error.') : 'OpenAI devolvio un error.';
				$requestId = $httpResponse->getHeaderLine('x-request-id');
				$lastFailure = [
					'status' => 0,
					'message' => $this->OpenIA_SanitizeError($message),
					'http_status' => $statusCode,
				];
				info('OpenAI request failed', [
					'method' => $method,
					'endpoint' => $url,
					'http_status' => $statusCode,
					'code' => is_array($error) ? ($error['code'] ?? null) : null,
					'request_id' => $requestId ?: null,
				]);

				if(!$this->OpenIA_IsRetryableStatus($statusCode) || $attempt === $attempts - 1){
					return $lastFailure;
				}
			}catch(GuzzleException $e){
				$statusCode = method_exists($e, 'hasResponse') && $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
				$lastFailure = [
					'status' => 0,
					'message' => 'No fue posible comunicarse con OpenAI.',
					'http_status' => $statusCode,
				];
				info('OpenAI transport failed', [
					'method' => $method,
					'endpoint' => $url,
					'http_status' => $statusCode,
				]);
				if(!$this->OpenIA_IsRetryableStatus($statusCode) || $attempt === $attempts - 1){
					return $lastFailure;
				}
			}catch(\Throwable $e){
				info('OpenAI client failed', ['method' => $method, 'endpoint' => $url, 'message' => $e->getMessage()]);
				return [
					'status' => 0,
					'message' => 'No fue posible preparar la solicitud a OpenAI.',
				];
			}

			usleep(250000 * (2 ** $attempt));
		}

		return $lastFailure ?: [
			'status' => 0,
			'message' => 'No fue posible completar la solicitud a OpenAI.',
		];
	}

	private function OpenIA_ExposeLegacyResponse(array $response)
	{
		if(($response['status'] ?? 0) !== 1){
			return [
				'status' => 0,
				'message' => $response['message'] ?? 'Error en la solicitud a OpenAI.',
				'messsage' => $response['message'] ?? 'Error en la solicitud a OpenAI.',
			];
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => collect($response['data'] ?? []),
		];
	}

	private function OpenIA_ExtractResponseText(array $response): array
	{
		$texts = [];
		if(isset($response['output_text']) && is_string($response['output_text'])){
			$texts[] = $response['output_text'];
		}
		foreach(($response['output'] ?? []) as $item){
			if(($item['type'] ?? null) !== 'message'){
				continue;
			}
			foreach(($item['content'] ?? []) as $content){
				if(($content['type'] ?? null) !== 'output_text' || !isset($content['text'])){
					continue;
				}
				if(is_string($content['text'])){
					$texts[] = $content['text'];
				}
			}
		}

		return array_values(array_unique(array_filter($texts, fn ($text) => trim((string) $text) !== '')));
	}

	private function OpenIA_AssistantProfile($assistant_id): array
	{
		$profile = [
			'model' => $this->OpenIA_GetModel('chat'),
			'instructions' => 'Eres un asistente virtual profesional de Opzio. Responde con claridad y no inventes informacion.',
		];
		$profiles = [
			$this->ENG_ASSISTANT_ID => [
				'model' => $this->OpenIA_GetModel('chat'),
				'instructions' => 'You are Maya, the professional virtual assistant of Opzio. Be concise, helpful, and never invent information.',
			],
			$this->ES_ASSISTANT_ID => [
				'model' => $this->OpenIA_GetModel('chat'),
				'instructions' => 'Eres Maya, la asistente virtual profesional de Opzio. Responde en espanol, de forma amable, clara y concisa. No inventes informacion.',
			],
			$this->ES_BLOG_ASSISTANT_ID => [
				'model' => $this->OpenIA_GetModel('content'),
				'instructions' => 'Eres un asistente editorial de Opzio. Genera contenido en espanol siguiendo exactamente el formato y la longitud solicitados.',
			],
		];
		$assistantId = (string) $assistant_id;

		if(isset($profiles[$assistantId])){
			$profile = array_merge($profile, $profiles[$assistantId]);
		}

		if(str_starts_with($assistantId, 'profile_')){
			try{
				$assistant = open_ia_assistant::where('assistant_id', $assistantId)->first();
				if($assistant){
					if($assistant->instructions){
						$profile['instructions'] = $assistant->instructions;
					}
					if($assistant->model){
						$storedModel = json_decode($assistant->model, true);
						$profile['model'] = is_string($storedModel) ? $storedModel : $assistant->model;
					}
				}
			}catch(\Throwable $e){
				info('OpenIA_AssistantProfile persistence error', ['message' => $e->getMessage()]);
			}
		}

		return $profile;
	}

	private function OpenIA_ResolveConversationId($thread_id): ?string
	{
		$threadId = trim((string) $thread_id);
		if($threadId === ''){
			return null;
		}
		if(isset($this->OpenIAConversationAliases[$threadId])){
			return $this->OpenIAConversationAliases[$threadId];
		}
		if(str_starts_with($threadId, 'conv_')){
			return $threadId;
		}

		$migration = $this->OpenIA_MigrateLegacyThread($threadId);
		if(($migration['status'] ?? 0) !== 1){
			return null;
		}
		$this->OpenIAConversationAliases[$threadId] = $migration['data']['id'];
		return $migration['data']['id'];
	}

	private function OpenIA_MigrateLegacyThread(string $threadId): array
	{
		$legacy = $this->OpenIA_RequestJson(
			'GET',
			'threads/'.$threadId.'/messages',
			null,
			['OpenAI-Beta' => 'assistants=v2'],
			['query' => ['order' => 'asc', 'limit' => 100]]
		);
		if(($legacy['status'] ?? 0) !== 1){
			return $legacy;
		}

		$items = [];
		foreach(($legacy['data']['data'] ?? []) as $message){
			$converted = $this->OpenIA_ConvertLegacyMessage($message);
			if($converted !== null){
				$items[] = $converted;
			}
		}
		$conversation = $this->OpenIA_RequestJson('POST', 'conversations', [
			'metadata' => ['migrated_from_thread' => $threadId],
			'items' => $items,
		]);
		if(($conversation['status'] ?? 0) !== 1){
			return $conversation;
		}

		$newId = $conversation['data']['id'];
		try{
			open_ia_thread::where('thread_id', $threadId)->update([
				'thread_id' => $newId,
				'object' => 'conversation',
				'createdAt' => (string) ($conversation['data']['created_at'] ?? time()),
				'metadata' => json_encode($conversation['data']['metadata'] ?? []),
			]);
			DB::table('client_chats')->where('thread_id', $threadId)->update(['thread_id' => $newId]);
			DB::table('open_ia_run_registers')->where('thread_id', $threadId)->update(['thread_id' => $newId]);
		}catch(\Throwable $e){
			info('OpenIA_MigrateLegacyThread persistence error', ['message' => $e->getMessage()]);
		}

		return [
			'status' => 1,
			'message' => 'success',
			'data' => $conversation['data'],
		];
	}

	private function OpenIA_ConvertLegacyMessage(array $message): ?array
	{
		$text = $this->OpenIA_ExtractContentText($message['content'] ?? []);
		if($text === null){
			return null;
		}
		$role = ($message['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
		return [
			'type' => 'message',
			'role' => $role,
			'content' => [[
				'type' => $role === 'assistant' ? 'output_text' : 'input_text',
				'text' => $text,
			]],
		];
	}

	private function OpenIA_NormalizeConversationItem(array $item): ?array
	{
		if(($item['type'] ?? 'message') !== 'message'){
			return null;
		}
		$text = $this->OpenIA_ExtractContentText($item['content'] ?? []);
		if($text === null){
			return null;
		}

		return [
			'id' => $item['id'] ?? null,
			'object' => 'thread.message',
			'created_at' => $item['created_at'] ?? null,
			'role' => $item['role'] ?? 'user',
			'content' => [[
				'type' => 'text',
				'text' => [
					'value' => $text,
					'annotations' => [],
				],
			]],
			'run_id' => null,
		];
	}

	private function OpenIA_ExtractContentText($content): ?string
	{
		if(is_string($content)){
			return $content;
		}
		foreach((array) $content as $part){
			if(!is_array($part)){ continue; }
			$text = $part['text'] ?? null;
			if(is_string($text)){ return $text; }
			if(is_array($text)){
				if(isset($text['value']) && is_string($text['value'])){ return $text['value']; }
				if(isset($text['text']) && is_string($text['text'])){ return $text['text']; }
			}
		}

		return null;
	}

	private function OpenIA_IsRetryableStatus($statusCode): bool
	{
		return in_array((int) $statusCode, [0, 408, 409, 429], true) || ((int) $statusCode >= 500 && (int) $statusCode <= 599);
	}

	private function OpenIA_SanitizeError($message): string
	{
		$message = trim((string) $message);
		return $message === '' ? 'OpenAI devolvio un error.' : mb_substr($message, 0, 300, 'UTF-8');
	}
}