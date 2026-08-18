<?php

namespace App\Http\Controllers;

use App\traits\contracts_trait;
use Illuminate\Http\Request;

class contracts_controller extends Controller
{
    use contracts_trait;

    private function response($response)
    {
        return $response['status'] == 1 ? $response : \Response::json($response, 400);
    }

    public function get_catalogs()
    {
        return $this->response($this->Contract_GetCatalogs());
    }

    public function get_page(Request $request)
    {
        return $this->response($this->Contract_GetPage($request->pagination, $request->search, $request->contract_type_id, $request->status, $request->contractable_type));
    }

    public function get_by_id(Request $request)
    {
        return $this->response($this->Contract_GetById($request->id ?? $request->contract_id));
    }

    public function add(Request $request)
    {
        return $this->response($this->Contract_CreateContract($request->all()));
    }

    public function update(Request $request)
    {
        return $this->response($this->Contract_UpdateContract($request->id, $request->all()));
    }

    public function delete(Request $request)
    {
        return $this->response($this->Contract_DeleteContract($request->id));
    }

    public function restore(Request $request)
    {
        return $this->response($this->Contract_RestoreContract($request->id));
    }

    public function generate(Request $request)
    {
        return $this->response($this->Contract_GenerateContract($request->id ?? $request->contract_id));
    }

    public function send(Request $request)
    {
        $recipients = $request->has('recipients') ? $request->input('recipients') : null;
        return $this->response($this->Contract_SendContract($request->id ?? $request->contract_id, $recipients));
    }

    public function get_send_options(Request $request)
    {
        return $this->response($this->Contract_GetSendOptions($request->id ?? $request->contract_id));
    }

    public function change_signature_status(Request $request)
    {
        return $this->response($this->Contract_ChangeSignatureStatus($request->id, $request->signature_status));
    }

    public function signature_pdf(Request $request)
    {
        return $this->Contract_DownloadSignaturePdf($request->id);
    }

    public function get_associated(Request $request)
    {
        return $this->response($this->Contract_GetAssociated($request->contractable_type, $request->contractable_id));
    }

    public function get_types()
    {
        return $this->response($this->Contract_GetTypes());
    }

    public function add_type(Request $request)
    {
        return $this->response($this->Contract_CreateType($request->name, $request->description, $request->active));
    }

    public function update_type(Request $request)
    {
        return $this->response($this->Contract_UpdateType($request->id, $request->name, $request->description, $request->active));
    }

    public function delete_type(Request $request)
    {
        return $this->response($this->Contract_DeleteType($request->id));
    }

    public function restore_type(Request $request)
    {
        return $this->response($this->Contract_RestoreType($request->id));
    }

    public function get_templates(Request $request)
    {
        return $this->response($this->Contract_GetTemplates($request->contract_type_id));
    }

    public function add_template(Request $request)
    {
        return $this->response($this->Contract_CreateTemplate($request->contract_type_id, $request->name, $request->subject, $request->content, $request->active, $request->variables ?? []));
    }

    public function update_template(Request $request)
    {
        return $this->response($this->Contract_UpdateTemplate($request->id, $request->contract_type_id, $request->name, $request->subject, $request->content, $request->active, $request->variables ?? []));
    }

    public function delete_template(Request $request)
    {
        return $this->response($this->Contract_DeleteTemplate($request->id));
    }

    public function restore_template(Request $request)
    {
        return $this->response($this->Contract_RestoreTemplate($request->id));
    }

}