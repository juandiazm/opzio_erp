<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

trait old_opzio_trait
{
	private $Client = null;
	public function Opzio_syh_GetConnection(){
		$this->Client = new Client([
			'base_uri' => 'https://old.opzio.com.co/API/',
			'timeout'  => 10.0,
			'verify' => false,
		]);
		return '1';
	}
	public function Opzio_syh_PostRequest($url, $SendData){
		try{
			if($this->Client == null){
				$this->Opzio_syh_GetConnection();
			}
			$myBody['AaBbCcDdEeFfGgHhIi'] = 'APIInterfaceApplication';
			if($SendData != null){
				foreach ($SendData as $key => $value) {
					$myBody[$key] = $value;
				}
			}
			$response = $this->Client->post($url, ['form_params'=>$myBody])->getBody()->getContents();
			if($response =='fail'){
				$response = collect();
			}
			return json_decode($response, true);
		}catch(\Exception $e){
			info('opzio_syh_trait '.$e->getMessage());
			return $e->getMessage();
		}
	}
}