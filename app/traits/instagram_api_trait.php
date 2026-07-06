<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

trait instagram_api_trait
{
	private $InstagramClient = null;
	public function InstagramAPI_GetConnection(){
		$this->InstagramClient = new Client([
			'base_uri' => 'https://graph.facebook.com/v19.0/',
			'timeout'  => 10.0,
			'verify' => false,
		]);
		return '1';
	}
	public function InstagramAPI_PostRequest($url, $queryParams, $SendData){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($this->InstagramClient == null){
				$this->InstagramAPI_GetConnection();
			}
			$response = $this->InstagramClient->post($url, [
				'query' => $queryParams,
				'form_params' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('InstagramAPI_PostRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function InstagramAPI_GetRequest($url){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($this->InstagramClient == null){
				$this->InstagramAPI_GetConnection();
			}
			$response = $this->InstagramClient->get($url)->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('InstagramAPI_GetRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
}