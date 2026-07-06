<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

trait freepik_api_trait
{
	private $FreePickClient = null;
	public function Freepik_GetConnection(){
		$this->FreePickClient = new Client([
			'base_uri' => 'https://api.freepik.com/v1/',
			'timeout'  => 10.0,
			'verify' => false,
			'headers' => [
				'x-freepik-api-key' => env('FREEPIK_API_KEY'),
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			],
		]);
		return '1';
	}
	public function Freepik_PostRequest($url, $queryParams, $SendData){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($this->FreePickClient == null){
				$this->Freepik_GetConnection();
			}
			/* add headers*/

			$response = $this->FreePickClient->post($url, [
				'query' => $queryParams,
				'form_params' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('Freepik_PostRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function Freepik_GetRequest($url){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($this->FreePickClient == null){
				$this->Freepik_GetConnection();
			}
			$response = $this->FreePickClient->get($url)->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('Freepik_GetRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
}