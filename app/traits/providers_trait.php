<?php 
namespace App\traits;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;

use App\Models\provider;
use App\Models\provider_document;
use App\Models\provider_contact;

use App\Models\country;
use App\Models\sector;

use Session;


trait providers_trait
{
    private $URL_CLIENTS_PATH = 'images/erp/providers/';
    public function Provider_AddProvider(
        $verified
        ,$active
        ,$name
        ,$lastname
        ,$email
        ,$identification_type
        ,$identification
        ,$country
        ,$address
        ,$phone
        ,$sector
        ,$description
        ,$photo
    ){
        try{
            $provider = provider::where('email', $email)->orWhere('identification', $identification)->first();
            if($provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor ya existe'
                ];
            }
            $provider = new provider();
            $provider->unique_id = strtoupper(Str::uuid()->toString());
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $provider->photo = $provider->unique_id.'.webp';
                $photo->save($this->URL_CLIENTS_PATH . $provider->photo);
                ImageOptimizer::optimize($this->URL_CLIENTS_PATH . $provider->photo);
            }
            $provider->name = $name;
            $provider->lastname = $lastname;
            $provider->email = $email;
            $provider->identification_type = $identification_type;
            $provider->identification = $identification;
            $provider->country = $country;
            $provider->address = $address;
            $provider->phone = $phone;
            $provider->sector = $sector;
            $provider->description = $description;
            $provider->verified = true;
            $provider->active = $active;
            $provider->save();
            //get provider country and sector
            $provider->country = country::where('id', $provider->country)->first();
            $provider->sector = sector::where('id', $provider->sector)->first();
            return [
                'status' => 1,
                'message' => 'proveedor agregado',
                'provider' => $provider
            ];
        }catch(\Exception $e){
            info('Provider_AddProvider error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_UpdateProvider(
        $id
        ,$verified
        ,$active
        ,$name
        ,$lastname
        ,$email
        ,$identification_type
        ,$identification
        ,$country
        ,$address
        ,$phone
        ,$sector
        ,$description
        ,$photo
    ){
        try{
            $provider = provider::where('id', $id)->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            $provider->name = $name;
            $provider->lastname = $lastname;
            $provider->email = $email;
            $provider->identification_type = $identification_type;
            $provider->identification = $identification;
            $provider->country = $country;
            $provider->address = $address;
            $provider->phone = $phone;
            $provider->sector = $sector;
            $provider->description = $description;
            $provider->verified = true;
            $provider->active = $active;
            if($photo){
                $photo = Image::make($photo)->encode('webp', 90);
                $provider->photo = $provider->unique_id.'.webp';
                $photo->save($this->URL_CLIENTS_PATH . $provider->photo);
                ImageOptimizer::optimize($this->URL_CLIENTS_PATH . $provider->photo);
            }
            $provider->save();
            return [
                'status' => 1,
                'message' => 'proveedor actualizado'
            ];
        }catch(\Exception $e){
            info('Provider_UpdateProvider error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_DeleteProvider(
        $id
    ){
        try{
            $provider = provider::where('id', $id)->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            $provider->delete();
            return [
                'status' => 1,
                'message' => 'proveedor eliminado'
            ];
        }catch(\Exception $e){
            info('Provider_DeleteProvider error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_RestoreProvider(
        $id
    ){
        try{
            $provider = provider::where('id', $id)->withTrashed()->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            $provider->restore();
            return [
                'status' => 1,
                'message' => 'proveedor restaurado'
            ];
        }catch(\Exception $e){
            info('Provider_RestoreProvider error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_GetProviderById(
        $id
    ){
        try{
            $provider = provider::where('id', $id)->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            return [
                'status' => 1,
                'message' => 'proveedor obtenido',
                'data' => $provider
            ];
        }catch(\Exception $e){
            info('Provider_GetProviderById error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_LoginProvider(
        $identification
        ,$password
    ){
        try{
            $provider = provider::where('email', $identification)->orWhere('identification', $identification)->orWhere('providername', $identification)->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            if(!Hash::check($password, $provider->password)){
                return [
                    'status' => 0,
                    'message' => 'La contraseña es incorrecta'
                ];
            }
            Session::put('provider', $provider);
            return [
                'status' => 1,
                'message' => 'proveedor logueado'
            ];
        }catch(\Exception $e){
            info('Provider_LoginProvider error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_GetPage(
        $pagination
        ,$search
        ,$with_trash = false
    ){
        try{
            if($with_trash == true){
                $providers = provider::withTrashed()->orderBy('name');
            }else{
                $providers = provider::orderBy('name');
            }
            if($search != null && $search != ''){
                $providers = $providers->where('name', 'like', '%'.$search.'%')
                ->orWhere('lastname', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('unique_id', 'like', '%'.$search.'%')
                ->orWhere('identification', 'like', '%'.$search.'%');
            }
            $pagination['total'] = $providers->count();
            $pagination['totalPages'] = ceil($pagination['total']/$pagination['per_page']);
            $providers = $providers->skip((($pagination['page']-1)*$pagination['per_page']))->take($pagination['per_page'])->get();
            $countries = country::whereIn('id', $providers->pluck('country'))->get();
            $sectors = sector::whereIn('id', $providers->pluck('sector'))->get();
            foreach($providers as $provider){
                $provider->country = $countries->firstWhere('id', $provider->country);
                $provider->sector = $sectors->firstWhere('id', $provider->sector);
            }
            return [
                'status' => 1,
                'message' => 'proveedors obtenidos',
                'pagination' => $pagination,
                'data' => $providers
            ];
        }catch(\Exception $e){
            info('Provider_GetPage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_CloseSession(){
        try{
            Session::forget('provider');
            return [
                'status' => 1,
                'message' => 'Sesión cerrada'
            ];
        }catch(\Exception $e){
            info('Provider_CloseSession error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    //Documents
    public function Provider_AddProviderDocument(
        $provider_id
        ,$name
        ,$file
    ){
        try{
            $provider = provider::where('id', $provider_id)->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            $accepted_format = ['pdf','docx','xlsx','pptx'];
            $file_format = strtolower($file->getClientOriginalExtension());
            if(($accepted_format == null || in_array($file_format, $accepted_format))){
                $uid = strtoupper(Str::uuid()->toString()).'.'.$file_format;
                Storage::disk('provider_document')->put($uid, file_get_contents($file));
                $document = new provider_document();
                $document->provider_id = $provider_id;
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
            info('Provider_AddProviderDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_GetProviderDocuments(
        $provider_id
        ,$search
    ){
        try{
            $documents = provider_document::where('provider_id', $provider_id)->orderBy('document_public_name');
            if($search != null && $search != ''){
                $documents = $documents->where('document_public_name', 'like', '%'.$search.'%');
            }
            $documents = $documents->get();
            foreach($documents as $document){
                $document->document_url = Storage::disk('provider_document')->url($document->document_private_name);
            }
            return [
                'status' => 1,
                'message' => 'Documentos obtenidos',
                'data' => $documents
            ];
        }catch(\Exception $e){
            info('Provider_GetProviderDocuments error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    public function Provider_UpdateProviderDocument(
        $id
        ,$name
    ){
        try{
            $document = provider_document::where('id', $id)->first();
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
            info('Provider_UpdateProviderDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    public function Provider_DeleteProviderDocument(
        $id
    ){
        try{
            $document = provider_document::where('id', $id)->first();
            if(!$document){
                return [
                    'status' => 0,
                    'message' => 'El documento no existe'
                ];
            }
            Storage::disk('provider_document')->delete($document->document_private_name);
            $document->delete();
            return [
                'status' => 1,
                'message' => 'Documento eliminado'
            ];
        }catch(\Exception $e){
            info('Provider_DeleteProviderDocument error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //CONTACTS
    //Add contact 
    public function Provider_AddProviderContact(
        $provider_id
        ,$name
        ,$email
        ,$phone
        ,$position
    ){
        try{
            $provider = provider::where('id', $provider_id)->first();
            if(!$provider){
                return [
                    'status' => 0,
                    'message' => 'El proveedor no existe'
                ];
            }
            $contact = new provider_contact();
            $contact->provider_id = $provider_id;
            $contact->name = $name;
            $contact->email = $email;
            $contact->phone = $phone;
            $contact->position = $position;
            $contact->save();
            return [
                'status' => 1,
                'message' => 'Contacto agregado',
                'id' => $contact->id
            ];
        }catch(\Exception $e){
            info('Provider_AddProviderContact error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Get contacts
    public function Provider_GetProviderContacts(
        $provider_id
        ,$search
        ,$with_trash = false
    ){
        try{
            $contacts = provider_contact::where('provider_id', $provider_id)->orderBy('name');
            if($search != null && $search != ''){
                $contacts = $contacts->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('position', 'like', '%'.$search.'%');
            }
            if($with_trash){
                $contacts = $contacts->withTrashed();
            }
            $contacts = $contacts->get();
            return [
                'status' => 1,
                'message' => 'Contactos obtenidos',
                'data' => $contacts
            ];
        }catch(\Exception $e){
            info('Provider_GetProviderContacts error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Update contact
    public function Provider_UpdateProviderContact(
        $id
        ,$name
        ,$email
        ,$phone
        ,$position
    ){
        try{
            $contact = provider_contact::where('id', $id)->first();
            if(!$contact){
                return [
                    'status' => 0,
                    'message' => 'El contacto no existe'
                ];
            }
            $contact->name = $name;
            $contact->email = $email;
            $contact->phone = $phone;
            $contact->position = $position;
            $contact->save();
            return [
                'status' => 1,
                'message' => 'Contacto actualizado'
            ];
        }catch(\Exception $e){
            info('Provider_UpdateProviderContact error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Delete contact
    public function Provider_DeleteProviderContact(
        $id
    ){
        try{
            $contact = provider_contact::where('id', $id)->first();
            if(!$contact){
                return [
                    'status' => 0,
                    'message' => 'El contacto no existe'
                ];
            }
            $contact->delete();
            return [
                'status' => 1,
                'message' => 'Contacto eliminado'
            ];
        }catch(\Exception $e){
            info('Provider_DeleteProviderContact error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
    //Restore contact
    public function Provider_RestoreProviderContact(
        $id
    ){
        try{
            $contact = provider_contact::where('id', $id)->withTrashed()->first();
            if(!$contact){
                return [
                    'status' => 0,
                    'message' => 'El contacto no existe'
                ];
            }
            $contact->restore();
            return [
                'status' => 1,
                'message' => 'Contacto restaurado'
            ];
        }catch(\Exception $e){
            info('Provider_RestoreProviderContact error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }   
    }
}