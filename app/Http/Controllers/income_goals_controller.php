<?php

namespace App\Http\Controllers;

use App\traits\income_goals_trait;
use Illuminate\Http\Request;

class income_goals_controller extends Controller
{
    use income_goals_trait;

    private function response($response)
    {
        return $response['status'] == 1 ? $response : \Response::json($response, 400);
    }

    public function get()
    {
        return $this->response($this->IncomeGoal_GetGoals());
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'frequency_months' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        return $this->response($this->IncomeGoal_Create($validated['target_amount'], $validated['frequency_months'], $validated['start_date'], $validated['end_date']));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:income_goals,id'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'frequency_months' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        return $this->response($this->IncomeGoal_Update($validated['id'], $validated['target_amount'], $validated['frequency_months'], $validated['start_date'], $validated['end_date']));
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:income_goals,id'],
        ]);

        return $this->response($this->IncomeGoal_Delete($validated['id']));
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:income_goals,id'],
        ]);

        return $this->response($this->IncomeGoal_Restore($validated['id']));
    }
}
