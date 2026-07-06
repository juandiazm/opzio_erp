<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\providers_trait;

class providers_controller extends Controller
{
    //
    use providers_trait;
    public function add_provider(Request $request){
        $Response = $this->Provider_AddProvider(
            $request->verified
            ,$request->state
            ,$request->name
            ,$request->lastname
            ,$request->email
            ,$request->identification_type
            ,$request->identification
            ,$request->country
            ,$request->address
            ,$request->phone
            ,$request->sector
            ,$request->description
            ,$request->photo
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_provider(Request $request){
        $Response = $this->Provider_UpdateProvider(
            $request->id
            ,$request->verified
            ,$request->state
            ,$request->name
            ,$request->lastname
            ,$request->email
            ,$request->identification_type
            ,$request->identification
            ,$request->country
            ,$request->address
            ,$request->phone
            ,$request->sector
            ,$request->description
            ,$request->photo
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_provider(Request $request){
        $Response = $this->Provider_DeleteProvider(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function restore_provider(Request $request){
        $Response = $this->Provider_RestoreProvider(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_provider_by_id(Request $request){
        $Response = $this->Provider_GetProviderById(
            $request->provider_id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_page(Request $request){
        $Response = $this->Provider_GetPage(
            $request->pagination,
            $request->search,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function add_provider_document(Request $request){
        $Response = $this->Provider_AddProviderDocument(
            $request->provider_id,
            $request->name,
            $request->file
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function get_provider_documents(Request $request){
        $Response = $this->Provider_GetProviderDocuments(
            $request->provider_id,
            $request->search
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function update_provider_document(Request $request){
        $Response = $this->Provider_UpdateProviderDocument(
            $request->id,
            $request->name
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    public function delete_provider_document(Request $request){
        $Response = $this->Provider_DeleteProviderDocument(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);
    }
    //CONTACTS
    //Add Contact
    public function add_provider_contact(Request $request){
        $Response = $this->Provider_AddProviderContact(
            $request->provider_id,
            $request->name,
            $request->email,
            $request->phone,
            $request->position
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //Get Contacts
    public function get_provider_contacts(Request $request){
        $Response = $this->Provider_GetProviderContacts(
            $request->provider_id,
            $request->search,
            true
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //Update Contact
    public function update_provider_contact(Request $request){
        $Response = $this->Provider_UpdateProviderContact(
            $request->id,
            $request->name,
            $request->email,
            $request->phone,
            $request->position
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //Delete Contact
    public function delete_provider_contact(Request $request){
        $Response = $this->Provider_DeleteProviderContact(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    //Restore Contact
    public function restore_provider_contact(Request $request){
        $Response = $this->Provider_RestoreProviderContact(
            $request->id
        );
        if($Response['status'] == 1){
            return $Response;
        }
        return \Response::json($Response , 400);    
    }
    
}
