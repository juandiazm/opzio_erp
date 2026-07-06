<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

trait facebook_api_trait
{
	private $FacebookClient = null;
	public function FacebookAPI_GetConnection(){
		$this->FacebookClient = new Client([
			'base_uri' => 'https://graph.facebook.com/v21.0/',
			'timeout'  => 10.0,
			'verify' => false,
		]);
		return '1';
	}
	public function FacebookAPI_PostRequest($url, $queryParams, $SendData){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($this->FacebookClient == null){
				$this->FacebookAPI_GetConnection();
			}
			$response = $this->FacebookClient->post($url, [
				'query' => $queryParams,
				'form_params' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(GuzzleHttp\Exception\ClientErrorResponseException $e){
			$response = $e->getResponse();
			info('FacebookAPI_PostRequest error: '.$response);
			$Response['message'] = $$response;
		}
		return $Response;
	}
	public function FacebookAPI_GetRequest($url){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($this->FacebookClient == null){
				$this->FacebookAPI_GetConnection();
			}
			$response = $this->FacebookClient->get($url)->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('FacebookAPI_GetRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
}