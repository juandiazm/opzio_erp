<?php

namespace App\Http\Controllers;

use App\traits\outcome_type_trait;
use Illuminate\Http\Request;

class outcome_type_controller extends Controller
{
    use outcome_type_trait;

    public function get_types(): \Illuminate\Http\JsonResponse|array
    {
        $response = $this->OutcomeType_GetTypes();

        return $response['status'] === 1
            ? $response
            : response()->json($response, 400);
    }

    public function add_type(Request $request): \Illuminate\Http\JsonResponse|array
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $response = $this->OutcomeType_AddType($request->input('name'));

        return $response['status'] === 1
            ? $response
            : response()->json($response, 400);
    }

    public function update_type(Request $request): \Illuminate\Http\JsonResponse|array
    {
        $request->validate([
            'id' => 'required|integer|exists:outcome_types,id',
            'name' => 'required|string|max:100',
        ]);

        $response = $this->OutcomeType_UpdateType((int) $request->input('id'), $request->input('name'));

        return $response['status'] === 1
            ? $response
            : response()->json($response, 400);
    }

    public function delete_type(Request $request): \Illuminate\Http\JsonResponse|array
    {
        $request->validate([
            'id' => 'required|integer|exists:outcome_types,id',
        ]);

        $response = $this->OutcomeType_DeleteType((int) $request->input('id'));

        return $response['status'] === 1
            ? $response
            : response()->json($response, 400);
    }
}