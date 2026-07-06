<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

trait twitter_api_trait
{
	private $TwitterClient = null;
	public function TwitterAPI_GetConnection(){
		$this->TwitterClient = new Client([
			'base_uri' => 'https://api.twitter.com/',
			'timeout'  => 10.0,
			'verify' => false,
			'headers' => [
				'X-Restli-Protocol-Version' => '2.0.0',
				'Twitter-Version' => '202404'
			],
		]);
		return '1';
	}
	public function TwitterAPI_PostRequest($url, $headers, $queryParams, $SendData, $baseUrl = null, $oauth = false){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($baseUrl != null){
				//use ouath if not null
				if($oauth == true){
					$stack = \GuzzleHttp\HandlerStack::create();
					$middleware = new \GuzzleHttp\Subscriber\Oauth\Oauth1([
						'consumer_key'    => env('TWITTER_API_KEY'),
						'consumer_secret' => env('TWITTER_API_SECRET'),
						'token'           => env('TWITTER_API_ACCESS_TOKEN'),
						'token_secret'    => env('TWITTER_API_ACCESS_SECRET')
					]);
					$stack->push($middleware);
					
					$this->TwitterClient = new Client([
						'base_uri' => $baseUrl,
						'timeout'  => 10.0,
						'verify' => false,
						'handler'  => $stack,
						'auth'     => 'oauth'
					]);
				}else{
					$this->TwitterClient = new Client([
						'base_uri' => $baseUrl,
						'timeout'  => 10.0,
						'verify' => false,
					]);
				}
				
			}else{
				$this->TwitterAPI_GetConnection();
			}
			//add headers
			//$headers['Content-Type'] = 'application/json';
			$response = $this->TwitterClient->post($url, [
				'headers' => $headers,
				'query' => $queryParams,
				'json' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch (ClientException $e) {
			// Maneja específicamente las excepciones de tipo ClientException
			info('TwitterAPI_PostRequest error: '.$e->getResponse()->getBody()->getContents());
			$Response['message'] = $e->getResponse()->getBody()->getContents();
		} catch (\Exception $e) {
			// Registra y maneja otras excepciones
			info('TwitterAPI_PostRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
			if ($e->hasResponse()) {
				$Response['data'] = (string) $e->getResponse()->getBody();
			}
		}
		return $Response;
	}
	public function TwitterAPI_PostRequestMultimedia($url, $headers, $queryParams, $SendData, $baseUrl = null, $oauth = false){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($baseUrl != null){
				//use ouath if not null
				if($oauth == true){
					$stack = \GuzzleHttp\HandlerStack::create();
					$middleware = new \GuzzleHttp\Subscriber\Oauth\Oauth1([
						'consumer_key'    => env('TWITTER_API_KEY'),
						'consumer_secret' => env('TWITTER_API_SECRET'),
						'token'           => env('TWITTER_API_ACCESS_TOKEN'),
						'token_secret'    => env('TWITTER_API_ACCESS_SECRET')
					]);
					$stack->push($middleware);
					
					$this->TwitterClient = new Client([
						'base_uri' => $baseUrl,
						'timeout'  => 10.0,
						'verify' => false,
						'handler'  => $stack,
						'auth'     => 'oauth'
					]);
				}else{
					$this->TwitterClient = new Client([
						'base_uri' => $baseUrl,
						'timeout'  => 10.0,
						'verify' => false,
					]);
				}
				
			}else{
				$this->TwitterAPI_GetConnection();
			}
			//add headers
			//$headers['Content-Type'] = 'application/json';
			$response = $this->TwitterClient->post($url, [
				'headers' => $headers,
				'query' => $queryParams,
				'multipart' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('TwitterAPI_PostRequestMultimedia error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function TwitterAPI_GetRequest($url){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			$this->TwitterAPI_GetConnection();
			$response = $this->TwitterClient->get($url)->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('TwitterAPI_GetRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function TwitterAPI_PutRequest($url, $headers, $queryParams, $SendData, $baseUrl = null){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($baseUrl != null){
				$this->TwitterClient = new Client([
					'base_uri' => $baseUrl,
					'timeout'  => 10.0,
					'verify' => false,
				]);
			}else{
				$this->TwitterAPI_GetConnection();
			}
			//add headers
			$headers['X-Restli-Protocol-Version'] = '2.0.0';
			$headers['Twitter-Version'] = '202404';
			$response = $this->TwitterClient->put($url, [
				'headers' => $headers,
				'query' => $queryParams,
				'multipart' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch (ClientException $e) {
			// Maneja específicamente las excepciones de tipo ClientException
			$Response['message'] = $e->getResponse()->getBody()->getContents();
		} catch (\Exception $e) {
			// Registra y maneja otras excepciones
			info('TwitterAPI_PutRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
			if ($e->hasResponse()) {
				$Response['data'] = (string) $e->getResponse()->getBody();
			}
		}
		return $Response;
	}
}