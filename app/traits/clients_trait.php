<?php 
namespace App\traits;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;

use App\Models\client;
use App\Models\client_document;
use App\Models\income;

use App\Models\country;
use App\Models\sector;
use App\Models\license;
use App\Models\client_user_permission;

use Session;

trait clients_trait
{
    use 
    client_users_trait
    ,mail_trait,
    siigo_new_trait
    ;

    private function Client_FormatSiigoSyncError($clientId, $siigoResponse){
        $base = "Error al sincronizar cliente {$clientId}";
        if(!is_array($siigoResponse)){
            return $base.': '.(string)$siigoResponse;
        }

        $message = $siigoResponse['message'] ?? 'Error desconocido';
        $decoded = json_decode($message, true);
        if(is_array($decoded) && isset($decoded['Errors']) && is_array($decoded['Errors']) && count($decoded['Errors']) > 0){
            $firstError = $decoded['Errors'][0];
            $code = $firstError['Code'] ?? 'unknown';
            $params = isset($firstError['Params']) && is_array($firstError['Params']) ? implode(',', $firstError['Params']) : '';
            $detail = $firstError['Message'] ?? $message;
            return $base." ({$code}".($params !== '' ? ": {$params}" : '')."): {$detail}";
        }

        return $base.': '.$message;
    }

    public $URL_CLIENTS_PATH = 'images/erp/clients/';
    public function Client_AddClient(
        $verified
        ,$active
        ,$name
        ,$lastname
        ,$email
        ,$identification_type
        ,$identification
        ,$country_id
        ,$address
        ,$phone
        ,$sector_id
        ,$description
        ,$photo
        ,$value_per_hour
        ,$electronic_invoice
        ,$send_client_email = true
    ){
        try{
            $client = client::where('email', $email)->orWhere('identification', $identification)->first();
            if($client){
                return [
                    'status' => 0,
                    'message' => 'El cliente ya existe'
                ];
            }
            $client = new client();
            $client->unique_id = strtoupper(Str::uuid()->toString());
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $client->photo = $client->unique_id.'.webp';
                $photo->save($this->URL_CLIENTS_PATH . $client->photo);
                ImageOptimizer::optimize($this->URL_CLIENTS_PATH . $client->photo);
            }
            $client->name = $name;
            $client->lastname = $lastname;
            $client->email = $email;
            $client->identification_type = $identification_type;
            $client->identification = $identification;
            $client->country_id = $country_id;
            $client->address = $address;
            $client->phone = $phone;
            $client->sector_id = $sector_id;
            $client->value_per_hour = $value_per_hour;
            $client->description = $description;
            $client->verified = $verified;
            $client->electronic_invoice = $electronic_invoice;
            if($verified == 1){
                $client->verified_date = Carbon::now();
            }
            $client->active = $active;

            // Crear cliente en Siigo
            // Asegurarnos de que el nombre y apellido sean válidos para Siigo
            $nameForSiigo = trim((string)$name);
            $lastnameForSiigo = trim((string)$lastname);
            
            $siigoResponse = $this->SiigoNew_AddClient(
                $identification,
                $nameForSiigo,
                $lastnameForSiigo,
                $email,
                $phone,
                $address,
                true // IVAMandatory
            );
            
            if ($siigoResponse['status'] == 1) {
                $siigo_id = $siigoResponse['data']['id'];
                $client->siigo_id = $siigo_id;
            } else {
                info('Error creating client in Siigo: ' . json_encode($siigoResponse));
            }

            $client->save();

            //get country data
            $client->country = country::where('id', $client->country_id)->first();
            //get sector data
            $client->sector = sector::where('id', $client->sector_id)->first();
            //add first client user
            $user_client_permissions = client_user_permission::select('id')->get()->pluck('id');
            $this->ClientUser_AddClientUser(
                $client->id
                ,$client->name
                ,null
                ,$client->email
                ,$client->email
                , null
                ,'Super Administrador'
                ,'#00057B'
                ,$user_client_permissions
                ,$send_client_email
            );
            $MailResponse = $this->Client_SendNewClientEmailToOpzio($client);
            return [
                'status' => 1,
                'message' => 'cliente agregado',
                'client' => $client
            ];
        }catch(\Exception $e){
            info('Client_AddClient error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_RegisterClient(
        $name
        ,$identification_type
        ,$identification
        ,$email
        ,$country_id
    ){
        $Response = [
            'status' => 0,
            'message' => 'Error'
        ];
        try{
            $client = client::where('email', $email)->orWhere('identification', $identification)->first();
            if($client){
                return [
                    'status' => 0,
                    'message' => 'El cliente ya existe'
                ];
            }
            $country = country::where('name', $country_id)->first();
            if(!$country){
                $country = new country();
                $country->name = $country_id;
                $country->save();
            }
            $client = new client();
            $client->unique_id = strtoupper(Str::uuid()->toString());
            $client->name = $name;
            $client->email = $email;
            $client->identification_type = $identification_type;
            $client->identification = $identification;
            $client->country_id = $country->id;
            $client->verified = 0;
            $client->active = 0;
            $client->save();
            //get country data
            $client->country = $country;
            //Add first client user
            $user_client_permissions = client_user_permission::select('id')->get()->pluck('id');
            $clientResponse = $this->ClientUser_AddClientUser(
                $client->id
                ,$name
                ,null
                ,$email
                ,$email
                ,null
                ,null
                ,'#0153FF'
                ,$user_client_permissions
            );
            $MailResponse = $this->Client_SendNewClientEmailToOpzio($client);
            $Response = [
                'status' => 1,
                'message' => 'Cliente registrado',
                'data' => null
            ];
        }catch(\Exception $e){
            info('Client_RegisterClient error: '.$e->getMessage());
            $Response['message'] = $e->getMessage();
        }
        return $Response;
    }
    public function Client_SendNewClientEmailToOpzio($client){
        try{
            if($client->active == 0){
                return [
                    'status' => 0,
                    'message' => 'El cliente no está activo'
                ];
            }
            //Send mail to opzio
            $Mails = [];
            $Mails[] = [
                'address' => 'comunicaciones@opzio.com.co',
                'name' => 'Opzio S.A.S.'
            ];
            $MailData = 
            [
                'subject' => 'Nuevo cliente registrado',
            ];
            $View = 'mail.client_registered';
            $ViewData = collect(
            [
                "client" => $client
            ]
            );
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            
            return [
                'status' => 1,
                'message' => 'Correo enviado'
            ];
        }catch(\Exception $e){
            info('Client_SendNewClientEmail error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_UpdateClient(
        $id
        ,$verified
        ,$active
        ,$name
        ,$lastname
        ,$email
        ,$identification_type
        ,$identification
        ,$country_id
        ,$address
        ,$phone
        ,$sector_id
        ,$value_per_hour
        ,$description
        ,$photo
        ,$electronic_invoice
    ){
        try{
            $client = client::where('id', $id)->first();
            if(!$client){
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe'
                ];
            }
            $client->name = $name;
            $client->lastname = $lastname;
            $client->email = $email;
            $client->identification_type = $identification_type;
            $client->identification = $identification;
            $client->country_id = $country_id;
            $client->address = $address;
            $client->phone = $phone;
            $client->sector_id = $sector_id;
            $client->value_per_hour = $value_per_hour;
            $client->description = $description;
            $client->electronic_invoice = $electronic_invoice;
            if($verified == 1 && $client->verified == 0){
                $client->verified_date = Carbon::now();
            }
            $client->verified = $verified;
            $client->active = $active;
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $client->photo = $client->unique_id.'.webp';
                $photo->save($this->URL_CLIENTS_PATH . $client->photo);
                ImageOptimizer::optimize($this->URL_CLIENTS_PATH . $client->photo);
            }
            $client->save();
            return [
                'status' => 1,
                'message' => 'cliente actualizado'
            ];
        }catch(\Exception $e){
            info('Client_UpdateClient error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_MyCompanyUpdateClient(
        $id
        ,$email
        ,$identification_type
        ,$country_id
        ,$address
        ,$phone
        ,$sector_id
        ,$photo
    ){
        try{
            $client = client::where('id', $id)->first();
            if(!$client){
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe'
                ];
            }
            $client->email = $email;
            $client->identification_type = $identification_type;
            $client->country_id = $country_id;
            $client->address = $address;
            $client->phone = $phone;
            $client->sector_id = $sector_id;
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $client->photo = $client->unique_id.'.webp';
                $photo->save($this->URL_CLIENTS_PATH . $client->photo);
                ImageOptimizer::optimize($this->URL_CLIENTS_PATH . $client->photo);
            }
            $client->save();
            Session::forget('client_user');
            return [
                'status' => 1,
                'message' => 'cliente actualizado'
            ];
        }catch(\Exception $e){
            info('Client_MyCompanyUpdateClient error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_GetClientById(
        $id
    ){
        try{
            $client = client::where('id', $id)->first();
            if(!$client){
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe'
                ];
            }
            //get country data
            $client->country = country::where('id', $client->country_id)->first();
            //get sector data
            $client->sector = sector::where('id', $client->sector_id)->first();
            return [
                'status' => 1,
                'message' => 'cliente obtenido',
                'data' => $client
            ];
        }catch(\Exception $e){
            info('Client_GetClientById error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_LoginClient(
        $identification
        ,$password
    ){
        try{
            $client = client::where('email', $identification)->orWhere('identification', $identification)->orWhere('clientname', $identification)->first();
            if(!$client){
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe'
                ];
            }
            if(!Hash::check($password, $client->password)){
                return [
                    'status' => 0,
                    'message' => 'La contraseña es incorrecta'
                ];
            }
            Session::put('client', $client);
            return [
                'status' => 1,
                'message' => 'cliente logueado'
            ];
        }catch(\Exception $e){
            info('Client_LoginClient error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_GetPage(
        $pagination
        ,$search
    ){
        try{
            $clients = client::orderBy('active', 'desc')->orderBy('name');
            if($search != null && $search != ''){
                $clients = $clients->where('name', 'like', '%'.$search.'%')
                ->orWhere('lastname', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('identification', 'like', '%'.$search.'%')
                ->orWhere('unique_id', 'like', '%'.$search.'%')
                ;
            }
            $pagination['total'] = $clients->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $clients = $clients->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            $countries = country::whereIn('id', $clients->pluck('country_id'))->get();
            $sectors = sector::whereIn('id', $clients->pluck('sector_id'))->get();
            $licenses = license::whereIn('client_id', $clients->pluck('id'))->get();
            foreach($clients as $client){
                //get country data
                $client->country = $countries->firstWhere('id', $client->country_id);
                //get sector data
                $client->sector = $sectors->firstWhere('id', $client->sector_id);
                //get licenses count
                $client->licenses_count = $licenses->where('client_id', $client->id)->count();
            }
            return [
                'status' => 1,
                'message' => 'clientes obtenidos',
                'pagination' => $pagination,
                'data' => $clients
            ];
        }catch(\Exception $e){
            info('Client_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_CloseSession(){
        try{
            Session::forget('client');
            return [
                'status' => 1,
                'message' => 'Sesión cerrada'
            ];
        }catch(\Exception $e){
            info('Client_CloseSession error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Documents
    public function Client_AddClientDocument(
        $client_id
        ,$name
        ,$file
    ){
        try{
            $client = client::where('id', $client_id)->first();
            if(!$client){
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe'
                ];
            }
            $accepted_format = ['pdf','docx','xlsx','pptx'];
            $file_format = strtolower($file->getClientOriginalExtension());
            if(($accepted_format == null || in_array($file_format, $accepted_format))){
                $uid = strtoupper(Str::uuid()->toString()).'.'.$file_format;
                Storage::disk('client_document')->put($uid, file_get_contents($file));
                $document = new client_document();
                $document->client_id = $client_id;
                $document->document_public_name = $name;
                $document->document_private_name = $uid;
                $document->save();
                return [
                    'status' => 1,
                    'message' => 'Documento agregado',
                    'id' => $document->id
                ];
            }
            return [
                'status' => 0,
                'message' => 'Formato de archivo no aceptado'
            ];
        }catch(\Exception $e){
            info('Client_AddClientDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_GetClientDocuments(
        $client_id
        ,$search
    ){
        try{
            $documents = client_document::where('client_id', $client_id)->orderBy('document_public_name');
            if($search != null && $search != ''){
                $documents = $documents->where('document_public_name', 'like', '%'.$search.'%');
            }
            $documents = $documents->get();
            foreach($documents as $document){
                $document->document_url = Storage::disk('client_document')->url($document->document_private_name);
            }
            return [
                'status' => 1,
                'message' => 'Documentos obtenidos',
                'data' => $documents
            ];
        }catch(\Exception $e){
            info('Client_GetClientDocuments error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Client_UpdateClientDocument(
        $id
        ,$name
    ){
        try{
            $document = client_document::where('id', $id)->first();
            if(!$document){
                return [
                    'status' => 0,
                    'message' => 'El documento no existe'
                ];
            }
            $document->document_public_name = $name;
            $document->save();
            return [
                'status' => 1,
                'message' => 'Documento actualizado'
            ];
        }catch(\Exception $e){
            info('Client_UpdateClientDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Client_DeleteClientDocument(
        $id
    ){
        try{
            $document = client_document::where('id', $id)->first();
            if(!$document){
                return [
                    'status' => 0,
                    'message' => 'El documento no existe'
                ];
            }
            Storage::disk('client_document')->delete($document->document_private_name);
            $document->delete();
            return [
                'status' => 1,
                'message' => 'Documento eliminado'
            ];
        }catch(\Exception $e){
            info('Client_DeleteClientDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Client_GetAll(
        $search
    ){
        try{
            $clients = client::orderBy('name');
            if($search != null && $search != ''){
                $clients = $clients->where('name', 'like', '%'.$search.'%')
                ->orWhere('lastname', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('identification', 'like', '%'.$search.'%');
            }
            $clients = $clients->get();
            return [
                'status' => 1,
                'message' => 'clientes obtenidos',
                'data' => $clients
            ];
        }catch(\Exception $e){
            info('Client_GetAll error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //STATISTICS
    public function Client_StatisticGetActiveClients(){
        try{
            $clients_count = client::where('active', 1)->count();
            return [
                'status' => 1,
                'message' => 'Clientes activos obtenidos',
                'data' => $clients_count
            ];
        }catch(\Exception $e){
            info('Client_StatisticGetActiveClients error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Client_StatisticGetNewClientsByDateRange(
        $date_from
        ,$date_to
    ){
        $Response = [
            'status' => 1,
            'message' => '',
            'data' => [
                'clients_by_month' => [],
                'clients_count' => 0,
                'clients_average' => 0,
                'clients_max' => 0,
                'clients_min' => 0,
                'month_labels' => [],
                'clients' => []
            ]
        ];
        try{
            $date_from = Carbon::parse($date_from)->startOfMonth();
            $date_to = Carbon::parse($date_to)->endOfMonth();
            $clients = client::
            where('verified', 1)
            ->whereBetween('verified_date', [$date_from, $date_to])
            ->get();
            $current_date = $date_from;
            while($current_date <= $date_to){
                $Response['data']['month_labels'][] = strtoupper($current_date->format('M'));
                $Response['data']['clients_by_month'][] = $clients->filter(function($client) use($current_date){
                    if($client->verified_date != null &&  Carbon::parse($client->verified_date)->format('Y-m') == $current_date->format('Y-m')){
                        return true;
                    }
                    return false;
                })->count();
                $current_date = $current_date->addMonth();
            }
            $Response['data']['clients_by_month'] = collect($Response['data']['clients_by_month']);
            $Response['data']['clients_count'] = $clients->count();
            $Response['data']['clients_average'] = round($clients->count()/count($Response['data']['month_labels']));
            $Response['data']['clients_max'] = $Response['data']['clients_by_month']->max();
            $Response['data']['clients_min'] = $Response['data']['clients_by_month']->min();
            $Response['data']['clients'] = $clients;
        }catch(\Exception $e){
            info('Client_StatisticGetNewClientsByDateRange error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Response;   
    }
    public function Client_GetClientsByDateRangeReport(
        $date_from
        ,$date_to
    ){
        $Reponse = [
            'status' => 0,
            'message' => 'No se encontraron usuarios',
            'data' => []
        ];
        try{
            $date_from = Carbon::parse($date_from);
            $date_to = Carbon::parse($date_to);
            $Reponse = $this->Client_GetClientsByDateRange($date_from, $date_to);
            if($Reponse['status'] == 0){
                return $Reponse;
            }
            $clients = $Reponse['data'];
            $date_diff = $date_to->diffInDays($date_from);
            if($date_diff < 31){
                $report = $clients->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('d M Y');
                })->map(function($grupped_clients) {
                    // Return the count of clients per day
                    return [
                        'label' => $grupped_clients->first()->created_at->format('d M Y'). ' - '.$grupped_clients->count(),
                        'total' => $grupped_clients->count()
                    ];
                });
                $all_days = collect();
                $current_day = $date_from->copy();
                while ($current_day->lessThanOrEqualTo($date_to)) {
                    $all_days->put($current_day->format('Y-m-d'), [
                        'label' => $current_day->format('d M Y'),
                        'total' => 0
                    ]);
                    $current_day->addDay();
                }
                $report = $all_days->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }else if($date_diff<365){
                //sum client by month
                $report = $clients->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('M Y');
                })->map(function($grupped_clients) {
                    // Return the count of clients per month
                    return [
                        'label' => $grupped_clients->first()->created_at->format('M Y'). ' - '.$grupped_clients->count(),
                        'total' => $grupped_clients->count()
                    ];
                });
                // Generate an array of all months within the range
                $all_months = collect();
                $current_month = $date_from->copy();
                while ($current_month->lessThanOrEqualTo($date_to)) {
                    $all_months->put($current_month->format('Y-m'), [
                        'label' => $current_month->format('M Y'),
                        'total' => 0
                    ]);
                    $current_month->addMonth();
                }
                $report = $all_months->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }else{
                //sum client by year
                $report = $clients->groupBy(function($date) {
                    return Carbon::parse($date->created_at)->format('Y');
                })->map(function($grupped_clients) {
                    // Return the count of clients per year
                    return [
                        'label' => $grupped_clients->first()->created_at->format('Y'). ' - '.$grupped_clients->count(),
                        'total' => $grupped_clients->count()
                    ];
                });
                // Generate an array of all years within the range
                $all_years = collect();
                $current_year = $date_from->copy();
                while ($current_year->lessThanOrEqualTo($date_to)) {
                    $all_years->put($current_year->format('Y'), [
                        'label' => $current_year->format('Y'),
                        'total' => 0
                    ]);
                    $current_year->addYear();
                }
                $report = $all_years->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }
            Storage::disk('reports')->put('clients'.Session::get('user')['unique_id'].'.json', json_encode($clients));
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de clientes obtenido',
                'data' => [
                    'clients' => $clients,
                    'report' => $report
                ]
            ];
        }catch(\Exception $e){
            info('Client_GetClientsByDateRangeReport error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function Client_GetClientsByDateRange(
        $date_from
        ,$date_to
    ){
        try{
            $clients = client::
            with(['country', 'sector'])
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->orderBy('created_at', 'asc')
            ->get();
            return [
                'status' => 1,
                'message' => 'Usuarios obtenidos',
                'data' => $clients
            ];
        }catch(\Exception $e){
            info('Client_GetClientsByDateRange error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    /*
     * Synchronize clients with Siigo
     */
    public function Client_SyncWithSiigo()
    {
        try {
            //get all clients from siigo
            $siigoClients = $this->SiigoNew_GetAllClients();
            $clients = client::whereNull('siigo_id')->get();
            foreach ($clients as $client) {
                //split with '-' to get identification
                $client_identification = explode('-', $client->identification)[0] ?? $client->identification;
                // Check if the client already exists in Siigo
                $existingClient = collect($siigoClients)->firstWhere('identification', $client_identification);
                if ($existingClient) {
                    // If the client exists, update the siigo_id
                    $client->siigo_id = $existingClient['id'];
                    $client->save();
                    continue;
                }
            }
            $clients = client::whereNull('siigo_id')->get();
            if ($clients->isEmpty()) {
                return [
                    'status' => 1,
                    'message' => 'Todos los clientes ya están sincronizados con Siigo',
                    'synced_count' => 0
                ];
            }

            $syncedCount = 0;
            $errors = [];

            foreach ($clients as $client) {
                try {
                    if(trim((string)$client->identification) === ''){
                        $errors[] = "Error al sincronizar cliente {$client->id}: identificación vacía";
                        continue;
                    }

                    $siigoResponse = $this->SiigoNew_AddClient(
                        $client->identification,
                        $client->name,
                        $client->lastname,
                        $client->email,
                        $client->phone,
                        $client->address,
                        true
                    );

                    if ($siigoResponse['status'] == 1 && isset($siigoResponse['data']['id'])) {
                        $client->siigo_id = $siigoResponse['data']['id'];
                        $client->save();
                        $syncedCount++;
                    } else {
                        $errors[] = $this->Client_FormatSiigoSyncError($client->id, $siigoResponse);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error al procesar cliente {$client->id}: " . $e->getMessage();
                }
            }
            
            return [
                'status' => 1,
                'message' => $syncedCount > 0 
                    ? "Se sincronizaron {$syncedCount} clientes con Siigo exitosamente" 
                    : "No se pudo sincronizar ningún cliente con Siigo",
                'synced_count' => $syncedCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            info('Error general durante la sincronización con Siigo: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => 'Error durante la sincronización: ' . $e->getMessage(),
                'synced_count' => 0
            ];
        }
    }
}