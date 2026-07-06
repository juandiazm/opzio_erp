<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

trait linkedin_api_trait
{
	private $LinkedInClient = null;
	public function LinkedInAPI_GetConnection(){
		$this->LinkedInClient = new Client([
			'base_uri' => 'https://api.linkedin.com/',
			'timeout'  => 10.0,
			'verify' => false,
			'headers' => [
				'X-Restli-Protocol-Version' => '2.0.0',
				'LinkedIn-Version' => '202404'
			],
		]);
		return '1';
	}
	public function LinkedInAPI_PostRequest($url, $headers, $queryParams, $SendData){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			$this->LinkedInAPI_GetConnection();
			//add headers
			$headers['Content-Type'] = 'application/json';
			$headers['X-Restli-Protocol-Version'] = '2.0.0';
			$headers['LinkedIn-Version'] = '202404';
			$response = $this->LinkedInClient->post($url, [
				'headers' => $headers,
				'query' => $queryParams,
				'json' => $SendData
			])->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('LinkedInAPI_PostRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function LinkedInAPI_GetRequest($url){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			$this->LinkedInAPI_GetConnection();
			$response = $this->LinkedInClient->get($url)->getBody()->getContents();
			$response = json_decode($response, true);
			$Response['status'] = 1;
			$Response['message'] = 'Success';
			$Response['data'] = $response;
		}catch(\Exception $e){
			info('LinkedInAPI_GetRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		return $Response;
	}
	public function LinkedInAPI_PutRequest($url, $headers, $queryParams, $SendData, $baseUrl = null){
		$Response = [
			'status' => 0,
			'message' => 'Error',
			'data' => null
		];
		try{
			if($baseUrl != null){
				$this->LinkedInClient = new Client([
					'base_uri' => $baseUrl,
					'timeout'  => 10.0,
					'verify' => false,
				]);
			}else{
				$this->LinkedInAPI_GetConnection();
			}
			//add headers
			$headers['X-Restli-Protocol-Version'] = '2.0.0';
			$headers['LinkedIn-Version'] = '202404';
			$response = $this->LinkedInClient->put($url, [
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
			info('LinkedInAPI_PutRequest error: '.$e->getMessage());
			$Response['message'] = $e->getMessage();
			if ($e->hasResponse()) {
				$Response['data'] = (string) $e->getResponse()->getBody();
			}
		}
		return $Response;
	}
}