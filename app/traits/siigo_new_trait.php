<?php 
namespace App\traits;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Illuminate\Support\Facades\App;

use \Carbon\Carbon;
use Session;

trait siigo_new_trait
{
	private $Client = null;
	private $username;
	private $access_key;
	private $partner_id;
	private $DocCodeFV = 26195;
	private $SiggoUserID = 279;
	public $Siggo_TaxAddName = "IVA 19%";
    public $Siggo_TaxAddId =  5806;
    public $Siggo_TaxAddPercentage = 19;
    public $Siggo_TaxDiscountName = "Retefuente 2.5%";
    public $Siggo_TaxDiscountId =  5812;
    public $Siggo_TaxDiscountPercentage = 2.50;
	private $siigo_token = null; // Store token in memory for console commands

	public function __construct() {
		$this->username = config('services.siigo.username');
		$this->access_key = config('services.siigo.access_key');
		$this->partner_id = config('services.siigo.partner_id');
	}

	public function SiggoNew_GetConnection(){
	 	$this->Client = new Client([
		    'base_uri' => 'https://api.siigo.com/',
		    'timeout'  => 30.0,
		    'verify' => false,
		]);
	    return '1';
	}
	public function SiggoNewGetTokken(){
		try{
			// Initialize credentials if not set (for console commands)
			$this->ensureCredentialsInitialized();
			
			$this->Client = null;
			if($this->Client == null){
				$this->SiggoNew_GetConnection();
			}
			$SendData = [
				"username"=> $this->username,
  				"access_key"=> $this->access_key
			];
			$response = $this->Client->post('auth', ['headers'=>[], 'json'=>$SendData])->getBody()->getContents();
			$token = json_decode($response, true)['access_token'];
			
			// Store token in both session (for web) and class property (for console)
			$this->siigo_token = $token;
			if(php_sapi_name() !== 'cli') {
				// Only use session if not in CLI mode
				Session::put('siggo_new_token', $token);
			}
			
			return $token;
		}catch(\Exception $e){
			info('siggo request error: '.$e->getMessage());
			return '0';
		}
	}
	
	// Helper method to ensure credentials are initialized
	private function ensureCredentialsInitialized(){
		if($this->username == null || $this->access_key == null || $this->partner_id == null){
			$this->username = config('services.siigo.username');
			$this->access_key = config('services.siigo.access_key');
			$this->partner_id = config('services.siigo.partner_id');
		}
	}
	
	// Helper method to get token from either session or class property
	private function getSiigoToken(){
		// First check class property (for console commands)
		if($this->siigo_token !== null){
			return $this->siigo_token;
		}
		// Then check session (for web requests)
		if(php_sapi_name() !== 'cli' && Session::has('siggo_new_token')){
			return Session::get('siggo_new_token');
		}
		return null;
	}
	
	// Helper method to check if token exists
	private function hasSiigoToken(){
		return $this->getSiigoToken() !== null;
	}

	private function normalizeSiigoText($value, $default = '', $maxLength = null){
		$value = trim((string)$value);
		if($value === ''){
			$value = $default;
		}
		if($maxLength !== null){
			$value = mb_substr($value, 0, $maxLength);
		}
		return $value;
	}

	private function normalizeSiigoPhoneNumber($phone){
		$digits = preg_replace('/\D+/', '', (string)$phone);
		if($digits === null || $digits === ''){
			return null;
		}
		if(strlen($digits) > 10){
			$digits = substr($digits, -10);
		}
		return $digits;
	}

	private function buildSiigoIdentificationData($identification){
		$raw = trim((string)$identification);
		if($raw === ''){
			return null;
		}

		$parts = explode('-', $raw);
		$identificationValue = preg_replace('/\D+/', '', $parts[0] ?? $raw);
		if($identificationValue === null || $identificationValue === ''){
			$identificationValue = preg_replace('/[^\w\-]/', '', $parts[0] ?? $raw);
		}
		if($identificationValue === null || $identificationValue === ''){
			return null;
		}

		$isCompany = count($parts) > 1;
		return [
			'identification' => $identificationValue,
			'person_type' => $isCompany ? 'Company' : 'Person',
			'id_type' => $isCompany ? '31' : '13',
		];
	}

	private function buildSiigoNameData($name, $lastname, $personType){
		$name = $this->normalizeSiigoText($name, '', 100);
		$lastname = $this->normalizeSiigoText($lastname, '', 100);

		if($personType === 'Company'){
			$companyName = trim($name.' '.$lastname);
			$companyName = $this->normalizeSiigoText($companyName, 'CLIENTE SIN NOMBRE', 100);
			return [
				'name' => [$companyName],
				'first_name' => $companyName,
				'last_name' => ''
			];
		}

		if($name === '' && $lastname !== ''){
			$name = $lastname;
			$lastname = '';
		}

		if($lastname === ''){
			$names = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
			if(count($names) > 1){
				$name = array_shift($names);
				$lastname = implode(' ', $names);
			}
		}

		$name = $this->normalizeSiigoText($name, 'NOMBRE', 100);
		$lastname = $this->normalizeSiigoText($lastname, 'APELLIDO', 100);

		return [
			'name' => [$name, $lastname],
			'first_name' => $name,
			'last_name' => $lastname
		];
	}

	private function getSiigoFiscalData($IVAMandatory){
		$vat_responsible = false;
		$fiscal_responsability = "R-99-PN";

		if($IVAMandatory == 1){
			$fiscal_responsability = "O-47";
			$vat_responsible = true;
		}elseif($IVAMandatory == 2){
			$fiscal_responsability = "O-23";
			$vat_responsible = true;
		}elseif($IVAMandatory == 3){
			$fiscal_responsability = "O-15";
			$vat_responsible = true;
		}elseif($IVAMandatory == 4){
			$fiscal_responsability = "O-13";
			$vat_responsible = true;
		}

		return [
			'vat_responsible' => $vat_responsible,
			'fiscal_responsability' => $fiscal_responsability
		];
	}

	private function buildSiigoCustomerPayload(
		$identification,
		$name,
		$lastname,
		$email,
		$phone,
		$address,
		$IVAMandatory
	){
		$identificationData = $this->buildSiigoIdentificationData($identification);
		if($identificationData == null){
			return [
				'status' => 0,
				'message' => 'Identificación inválida para Siigo',
				'data' => []
			];
		}

		$nameData = $this->buildSiigoNameData($name, $lastname, $identificationData['person_type']);
		$fiscalData = $this->getSiigoFiscalData($IVAMandatory);
		$address = $this->normalizeSiigoText($address, 'SIN DIRECCION', 256);
		$email = trim((string)$email);
		$phoneNumber = $this->normalizeSiigoPhoneNumber($phone);

		$payload = [];
		$payload["type"] = "Customer";
		$payload["person_type"] = $identificationData['person_type'];
		$payload["id_type"] = $identificationData['id_type'];
		$payload["identification"] = $identificationData['identification'];
		$payload["name"] = $nameData['name'];
		$payload["commercial_name"] = $this->normalizeSiigoText(implode(' ', $nameData['name']), 'CLIENTE', 100);
		$payload["branch_office"] = 0;
		$payload["active"] = true;
		$payload["fiscal_responsibilities"] = [
			[
				"code" => $fiscalData['fiscal_responsability']
			]
		];
		$payload["vat_responsible"] = $fiscalData['vat_responsible'];
		$payload["address"] = [
			"address" => $address,
			"city" => [
				"country_code" => "Co",
				"state_code" => "11",
				"city_code" => "11001"
			],
			"postal_code" => "110110"
		];

		if($phoneNumber !== null){
			$payload["phones"] = [
				[
					"indicative" => "57",
					"number" => (string)$phoneNumber,
					"extension" => "132"
				]
			];
		}

		$contact = [
			"first_name" => $nameData['first_name'],
			"last_name" => $nameData['last_name']
		];
		if($email !== ''){
			$contact['email'] = $email;
		}
		if($phoneNumber !== null){
			$contact['phone'] = [
				"indicative" => "57",
				"number" => (string)$phoneNumber,
				"extension" => "132"
			];
		}
		$payload["contacts"] = [$contact];
		$payload["comments"] = "Cliente creado desde el sistema ERP";

		return [
			'status' => 1,
			'message' => 'success',
			'data' => $payload
		];
	}

	public function SiigoNew_PostRequest($url, $headers, $SendData){
		try{
			$this->ensureCredentialsInitialized();
			
			$this->Client = null;
			if($this->Client == null){
				$this->SiggoNew_GetConnection();
			}
			$headers['Partner-Id'] = $this->partner_id;
			/*$myBody = [];
			if($SendData != null){
				foreach ($SendData as $key => $value) {
					$myBody[$key] = $value;
				}
			}*/
			//$myBody = json_encode($SendData);
			
			//return '0';
			//if (App::environment() === 'production') {
				$response = $this->Client->post($url, ['headers'=>$headers, 'json'=>$SendData])->getBody()->getContents();
				return [
					'status' => '1',
					'data' => collect(json_decode($response, true)),
					'message' => 'success'
				];
			//}else{
			//	return '0';
			//}
		}catch(\GuzzleHttp\Exception\ClientException $e){
            $error = $e->getResponse()->getBody()->getContents();
            info('SiigoNew_PostRequest error: '. $error);
            return [
				'status' => 0,
				'data' => $error,
				'message' => $error
			];;
        }
	}
	public function SiggoNew_PutRequest($url, $headers, $SendData){
		try{
			$this->ensureCredentialsInitialized();
			
			$this->Client = null;
			if($this->Client == null){
				$this->SiggoNew_GetConnection();
			}
			$headers['Partner-Id'] = $this->partner_id;
			/*$myBody = [];
			if($SendData != null){
				foreach ($SendData as $key => $value) {
					$myBody[$key] = $value;
				}
			}*/
			//$myBody = json_encode($SendData);
			
			//return '0';
			//if (App::environment() === 'production') {
				$response = $this->Client->put($url, ['headers'=>$headers, 'json'=>$SendData])->getBody()->getContents();
				return $response;
			//}else{
			//	return '0';
			//}
		}catch(\GuzzleHttp\Exception\ClientException $e){
            $error = $e->getResponse()->getBody()->getContents();
			info('SiggoNew_PutRequest error: '. $error);
            return $error;
        }
	}
	public function SiggoNew_GetRequest($url, $headers, $SendData){
		try{
			$this->ensureCredentialsInitialized();
			
			$this->Client = null;
			if($this->Client == null){
				$this->SiggoNew_GetConnection();
			}
			$headers['Partner-Id'] = $this->partner_id;
			$myBody = [];
			if($SendData != null){
				foreach ($SendData as $key => $value) {
					$myBody[$key] = $value;
				}
			}

			//return '0';
			//if (App::environment() === 'production') {
				$response = $this->Client->get($url, ['headers'=>$headers, 'query'=>$myBody])->getBody()->getContents();
				return $response;
			//}else{
			//	return '0';
			//}
		}catch(\GuzzleHttp\Exception\ClientException $e){
            $error = $e->getResponse()->getBody()->getContents();
            info('SiggoNew_GetRequest error: '. $error);
            return $error;
        }
	}
	public function SiggoNew_DeleteRequest($url, $headers, $SendData){
		try{
			$this->ensureCredentialsInitialized();
			
			$this->Client = null;
			$headers['Partner-Id'] = $this->partner_id;
			if($this->Client == null){
				$this->SiggoNew_GetConnection();
			}
			$myBody = [];
			if($SendData != null){
				foreach ($SendData as $key => $value) {
					$myBody[$key] = $value;
				}
			}

			//return '0';
			if (App::environment() === 'production') {
				$response = $this->Client->delete($url, ['headers'=>$headers, 'form_params'=>$myBody])->getBody()->getContents();
				return $response;
			}else{
				return '0';
			}
		}catch(\GuzzleHttp\Exception\ClientException $e){
            $error = $e->getResponse()->getBody()->getContents();
            info('SiggoNew_DeleteRequest error: '. $error);
            return $error;
        }
	}
	///PETICIONES DE PRODUCTO
	///PETICIONES DE PRODUCTO
	///PETICIONES DE PRODUCTO
	///PETICIONES DE PRODUCTO
	//////PETICIONES DE PRODUCTO
	///PETICIONES DE PRODUCTO
	//////PETICIONES DE PRODUCTO
	///PETICIONES DE PRODUCTO
	//////PETICIONES DE PRODUCTO
	///PETICIONES DE PRODUCTO
	public function SiggoNew_Create_New_Product($code, $name){
		if(!$this->hasSiigoToken()){
			$this->SiggoNewGetTokken();
		}
		$headers = [
    		'Authorization' => 'Bearer '.$this->getSiigoToken(),
    		'Accept' => 'application/json',
    	];
		$SendData = [
    		"code"=> ''.$code,
			"name"=> $name,
			"account_group"=> 2450,
			//"account_group"=> 1549,
			"type"=> "Product",
			"stock_control"=> false,
			"active"=> true,
			"tax_classification"=> "Taxed",
			"tax_included"=> true,
			"tax_consumption_value"=> 0,
			"unit"=> "94",
			"unit_label"=> "Unidades",
			"description"=> $name
    	];
		$requestResponse = [
			'status' => 0,
			'message' => '',
			'data' => []
		];
		
		try{
			$result = $this->SiigoNew_PostRequest('v1/products', $headers, $SendData);
			if($result['status'] == 1){
				$information = collect($result['data']);
				if($information->has('id')){
					$requestResponse['status'] = 1;
					$requestResponse['message'] = 'success';
					$requestResponse['data'] = $information;
				}else{
					$requestResponse['message'] = 'error id not found';
				}
			}else{
				$requestResponse['message'] = 'error';
			}
    	}catch(\Exception $e){
    		info('Create new product siggo error: '.$e->getMessage());
			$requestResponse['message'] = $e->getMessage();
		}
		return $requestResponse;
    }
    public function SiggoNew_Update_Product($id, $code, $name){
		if(!$this->hasSiigoToken()){
			$this->SiggoNewGetTokken();
		}
		$headers = [
    		'Authorization' => 'Bearer '.$this->getSiigoToken(),
    		'Accept' => 'application/json',
    	];
    	$SendData = [
    		"code"=> ''.$code,
			"name"=> $name,
			"description"=> $name,
			//"account_group"=> 1549
			"account_group"=> 2450,
    	];
		$requestResponse = [
			'status' => 0,
			'message' => '',
			'data' => []
		];
		try{
			$urls = 'v1/products/'.$id;
			$result = $this->SiggoNew_PutRequest($urls, $headers, $SendData);
			$information = collect(json_decode($result, true));
			if($information->has('id')){
				$requestResponse['status'] = 1;
				$requestResponse['message'] = 'success';
			}else{
				$requestResponse['message'] = 'error id not found';
			}
			$requestResponse['data'] = [
				'information' => $information,
				'result' => $SendData,
				'id' => $id
			];
		}catch(\Exception $e){
			info('Update new product siggo error: '.$e->getMessage());
			$requestResponse['message'] = $e->getMessage();
		}
		return $requestResponse;
	}
    public function SiggoNew_Delete_Product($electronic_bill_id){
    	if(!$this->hasSiigoToken()){
			$this->SiggoNewGetTokken();
		}
		$headers = [
    		'Authorization' => $this->getSiigoToken(),
    		'Accept' => 'application/json',
    	];
    	$SendData = [
			"id"=> $electronic_bill_id,
  			"deleted"=> true
		];
    	$result = $this->SiggoNew_DeleteRequest('/v1/products/id_', $headers, $SendData);
    	try{
    		if($result==null){
    			return '1';
    		}else{
    			return '-1';
    		}
    	}catch(\Exception $e){
    		info('Delete product siggo error: '.$e->getMessage());
			return '-1';
    	}
    }
    public function SiggoNew_Get_Product($identifier, $By_id){
    	if(!$this->hasSiigoToken()){
			$this->SiggoNewGetTokken();
		}
    	$headers = [
    		'Ocp-Apim-Subscription-Key' => $this->SubscriptionKey,
    		'Authorization' => $this->getSiigoToken(),
    	];
    	if($By_id){
    		$SendData = [
	    		'namespace' => 'v1'
	    	];
    		$result = $this->SiggoNew_GetRequest('Products/GetByID/'.$identifier, $headers, $SendData);
    		try{
	    		$information = collect(json_decode($result, true));
	    		if($information['Id'] == '0'){
	    			return null;
	    		}else{
	    			return $information;
	    		}
	    	}catch(\Exception $e){
	    		info('Get product siggo error: '.$e->getMessage());
				return '-1';
	    	}
    	}else{
    		$SendData = [
	    		'codes' => "'".$identifier."'",
	    		'namespace' => 'v1'
	    	];
    		$result = $this->SiggoNew_GetRequest('Products/GetAllByCode', $headers, $SendData);
    		try{
	    		$information = collect(json_decode($result, true));
	    		if(count($information)==0){
	    			return null;
	    		}else{
	    			return $information[0];
	    		}
	    		
	    	}catch(\Exception $e){
	    		info('Get product siggo error: '.$e->getMessage());
				return null;
	    	}
    	}
		
    }
	public function SiggoNew_GetAllProducts(){
		if(!$this->hasSiigoToken()){
			$this->SiggoNewGetTokken();
		}
		$headers = [
    		'Authorization' => 'Bearer '.$this->getSiigoToken(),
    		'Accept' => 'application/json',
    	];
    	$SendData = [
			'namespace' => 'v1'
		];
		$result = $this->SiggoNew_GetRequest('v1/products?created_start=2015-02-17', $headers, null);
		try{
			//Session::flush();return [111];
			if(Session::has('siigo_last_pagination')){
				$pagination = Session::get('siigo_last_pagination');
			}else{
				$pagination = [
					"page"=> 1,
					"page_size"=> 50,
					"total_results"=> 0
				];
			}
			if(Session::has('siggo_new_products')){
				$AllProducts = Session::get('siggo_new_products');
			}else{
				$AllProducts  = [];
			}
			if($pagination['total_results'] < count($AllProducts)){
				return $AllProducts;
			}
			$continue = true;
			$pages = 100;
			do{
				$information = collect(json_decode($result, true));
				$url = 'v1/products?page='.$pagination['page'].'&page_size='.$pagination['page_size'];
				$SendData = $pagination;
				$result = $this->SiggoNew_GetRequest($url, $headers, $SendData);
				$result = collect(json_decode($result, true));
				$first = true;
				if($result->has('pagination') && $result->has('results')){
					if($first == true){
						$pagination['total_results'] = $result['pagination']['total_results'];
						$first = false;
					}
					$pagination['page'] = $pagination['page']+1;
					foreach ($result['results'] as $key => $value) {
						$AllProducts[] = $value;
					}
					Session::put('siggo_new_products', $AllProducts);
					Session::put('siigo_last_pagination', $pagination);
					sleep(1);
				}else{
					$continue = false;
				}
			}while($continue && /*count($AllProducts)<500 &&*/ count($AllProducts)<$pagination['total_results']);
			
			return $AllProducts;
		}catch(\Exception $e){
			info('Get product siggo error: '.$e->getMessage());
			return [
				'status' => 0,
				'message' => $e->getMessage(),
				'data' => []
			];
		}
	}
   //Add a new client
   public function SiigoNew_AddClient(
		$identification
		,$name
		,$lastname
		,$email
		,$phone
		,$address
		,$IVAMandatory
	){
		try{
			if(!$this->hasSiigoToken()){
				$this->SiggoNewGetTokken();
			}
			$headers = [
				'Authorization' => 'Bearer '.$this->getSiigoToken(),
				'Accept' => 'application/json',
			];
			$url = 'v1/customers';
			$payloadResponse = $this->buildSiigoCustomerPayload(
				$identification,
				$name,
				$lastname,
				$email,
				$phone,
				$address,
				$IVAMandatory
			);
			if($payloadResponse['status'] == 0){
				return $payloadResponse;
			}
			$SendData = $payloadResponse['data'];
			info('SendData: '.json_encode($SendData));
			$Response = $this->SiigoNew_PostRequest($url, $headers, $SendData);
			if($Response['status'] == 1){
				return [
					'status' => 1,
					'message' => 'success',
					'data' => $Response['data']
				];
			}else{
				return [
					'status' => 0,
					'message' => $Response['message'],
					'data' => []
				];
			}
			
		}catch(\Exception $e){
			info('SiigoNew_AddProduct error: '.$e->getMessage());
			$Response = [
				'status' => 0,
				'message' => $e->getMessage(),
				'data' => []
			];
		}
	}
	//Update a client
	public function SiigoNew_UpdateClient(
		$siigo_client_id
		,$identification
		,$name
		,$lastname
		,$email
		,$phone
		,$address
		,$IVAMandatory
	){
		try{
			if(!$this->hasSiigoToken()){
				$this->SiggoNewGetTokken();
			}
			$headers = [
				'Authorization' => 'Bearer '.$this->getSiigoToken(),
				'Accept' => 'application/json',
			];
			$url = 'v1/customers/'.$siigo_client_id;
			$payloadResponse = $this->buildSiigoCustomerPayload(
				$identification,
				$name,
				$lastname,
				$email,
				$phone,
				$address,
				$IVAMandatory
			);
			if($payloadResponse['status'] == 0){
				return [
					'status' => 0,
					'message' => $payloadResponse['message'],
					'data' => []
				];
			}
			$SendData = $payloadResponse['data'];
			$result = $this->SiggoNew_PutRequest($url, $headers, $SendData);
			try{
				$information = collect(json_decode($result, true));
				if($information->has('id')){
					return $information;
				}else{
					return '-1';
				}
			}catch(\Exception $e){
				info('Update new product siggo error: '.$e->getMessage());
				return '-1';
			}
			
		}catch(\Exception $e){
			info('SiigoNew_UpdateClient error: '.$e->getMessage());
			$Response = [
				'status' => 0,
				'message' => $e->getMessage(),
				'data' => []
			];
		}
	}
	//Get all clients
	public function SiigoNew_GetAllClients(
		$created_start = '2015-01-01'
	){
		if(!$this->hasSiigoToken()){
			$this->SiggoNewGetTokken();
		}
		$headers = [
			'Authorization' => 'Bearer '.$this->getSiigoToken(),
			'Accept' => 'application/json',
		];
    	
		$SendData = [];
		$page = 0;
		$pageSize = 100;
		$totalClients = 0;
		$abort = false;
		$Clients = [];
		do{
			$page = $page+1;
			try{
				$url = 'v1/customers';
				$SendData['page'] = $page;
				$SendData['page_size'] = $pageSize;
				$SendData['created_start'] = $created_start;
				$result = $this->SiggoNew_GetRequest($url, $headers, $SendData);
				$information = collect(json_decode($result, true));
				//return $information['pagination']['page_size'];
				$pageSize = $information['pagination']['page_size'];
				$totalClients = $information['pagination']['total_results'];
				$Clients = array_merge($Clients, $information['results']);
				sleep(2);
			}catch(\Exception $e){
				$Clients = $e->getMessage();
				$abort = true;
			}
		}while(!$abort && (($page*$pageSize) < $totalClients));
		Session::put('siggo_new_clients', $Clients);
		return $Clients;
    	
	}
	//Get payment methods
	public function SiigoNew_GetPaymentMethods(){
		try{
			if(!$this->hasSiigoToken()){
				$this->SiggoNewGetTokken();
			}
			$headers = [
				'Authorization' => 'Bearer '.$this->getSiigoToken(),
				'Accept' => 'application/json',
			];
			$url = 'v1/payment-types';
			$SendData = [
				'document_type' => 'FV' // FV for Factura de Venta (Sales Invoice)
			];
			$result = $this->SiggoNew_GetRequest($url, $headers, $SendData);
			$information = collect(json_decode($result, true));
			return [
				'status' => 1,
				'message' => 'success',
				'data' => $information
			];
		}catch(\Exception $e){
			info('SiigoNew_GetPaymentMethods error: '.$e->getMessage());
			return [
				'status' => 0,
				'message' => $e->getMessage(),
				'data' => []
			];
		}
	}
	//Create Electronic bill
	public function SiigoNew_CreateNewElectronicBill(
		$date
		,$client_identification
		,$branch_office
		,$items
		,$payments
		,$observations
		,$purchase_order
		,$attemps = 1
	){
		try{
			if(!$this->hasSiigoToken()){
				$this->SiggoNewGetTokken();
			}
			$headers = [
				'Authorization' => 'Bearer '.$this->getSiigoToken(),
				'Accept' => 'application/json',
			];
			$client_identification = explode('-', $client_identification);
			if(count($client_identification)>0){
				$client_identification = $client_identification[0];
			}
			///////////////////////////////////////////////
			$url = 'v1/invoices';
			$SendData = [];
			$SendData["stamp"] = [
				"send" => true
			];
			$SendData["mail"] = [
				"send" => true
			];
			$SendData["document"]= [
				"id"=> $this->DocCodeFV
			];
			$SendData["date"]= $date->format('Y-m-d');
			$SendData["customer"]= [
				"identification" => $client_identification,
				"branch_office" =>  $branch_office,
			];
			$SendData["seller"] = $this->SiggoUserID;
			$SendData["observations"]= $observations.' Registro automático API opzio.com.co';
			if($purchase_order != null && $purchase_order != ''){
				//get last 20 characters
				$purchase_order = substr($purchase_order, -20);
				$SendData["additional_fields"]= [
					"purchase_order"=> [
						"prefix"=> "",
						"number"=> $purchase_order
					]
				];
			}
			$SendData["items"]= $items;
			
			
			$SendData["payments"] = $payments;
			$Response = $this->SiigoNew_PostRequest($url, $headers, $SendData);
			if($Response['status'] == 1){
				$Response =  [
					'status' => 1,
					'message' => 'success',
					'data' => $Response['data']
				];
			}else{
				if(str_contains($Response['message'],'invalid_total_payments')){
					if($attemps == 1){
						preg_match_all('/(\+|-){0,1}\d+\.{0,1}\d*/', $Response['message'], $matches);
						$difference = -0.01;
						$attemps=$attemps+1;
						sleep(3);
						if(count($matches[0])>1){
							$payments[0]['value'] = $matches[0][1];
						}else{
							$payments[0]['value'] = round($payments[0]['value']-0.01, 2);
						}
						
						return $this->SiigoNew_CreateNewElectronicBill(
							$date
							,$client_identification
							,$branch_office
							,$items
							,$payments
							,$observations
							,$purchase_order
							,$attemps
						);
					}else if($attemps == 2){
						$attemps=$attemps+1;
						sleep(3);
						$payments[0]['value'] = round($payments[0]['value']+0.02, 2);
						return $this->SiigoNew_CreateNewElectronicBill(
							$date
							,$client_identification
							,$branch_office
							,$items
							,$payments
							,$observations
							,$purchase_order
							,$attemps
						);
					}
				}
				$Response = [
					'status' => 0,
					'message' => $Response['message'],
					'data' => []
				];
			}
		}catch(\Exception $e){
			info('SiigoNew_CreateNewElectronicBill error: '.$e->getMessage());
			$Response = [
				'status' => 0,
				'message' => $e->getMessage(),
				'data' => []
			];
		}
		return $Response;
	}
	
}