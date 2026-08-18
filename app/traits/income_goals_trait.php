<?php

namespace App\traits;

use App\Models\income_goal;
use Carbon\Carbon;

trait income_goals_trait
{
    public function IncomeGoal_GetGoals()
    {
        try {
            $goals = income_goal::withTrashed()->orderBy('created_at', 'desc')->get();

            return [
                'status' => 1,
                'message' => 'Metas obtenidas',
                'data' => $goals,
            ];
        } catch (\Exception $e) {
            info('IncomeGoal_GetGoals error: '.$e->getMessage());

            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function IncomeGoal_ValidateRange($startDate, $endDate, $frequencyMonths)
    {
        try {
            $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endDate)->startOfDay();
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => 'El rango de fechas no es válido',
            ];
        }

        if ($start->greaterThan($end)) {
            return [
                'status' => 0,
                'message' => 'El inicio del rango no puede ser posterior al fin',
            ];
        }

        if ($start->day !== 1 || $end->day !== $end->copy()->endOfMonth()->day) {
            return [
                'status' => 0,
                'message' => 'El rango debe iniciar el primer día y terminar el último día de un mes',
            ];
        }

        $totalMonths = (($end->year - $start->year) * 12) + ($end->month - $start->month) + 1;
        if ($totalMonths % (int) $frequencyMonths !== 0) {
            return [
                'status' => 0,
                'message' => 'La cantidad de meses del rango debe ser múltiplo de la frecuencia',
            ];
        }

        return [
            'status' => 1,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];
    }

    public function IncomeGoal_Create($targetAmount, $frequencyMonths, $startDate, $endDate)
    {
        try {
            $range = $this->IncomeGoal_ValidateRange($startDate, $endDate, $frequencyMonths);
            if ($range['status'] !== 1) return $range;

            $goal = income_goal::create([
                'target_amount' => $targetAmount,
                'frequency_months' => $frequencyMonths,
                'start_date' => $range['start_date'],
                'end_date' => $range['end_date'],
            ]);

            return [
                'status' => 1,
                'message' => 'Meta agregada',
                'data' => $goal,
            ];
        } catch (\Exception $e) {
            info('IncomeGoal_Create error: '.$e->getMessage());

            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function IncomeGoal_Update($id, $targetAmount, $frequencyMonths, $startDate, $endDate)
    {
        try {
            $range = $this->IncomeGoal_ValidateRange($startDate, $endDate, $frequencyMonths);
            if ($range['status'] !== 1) return $range;

            $goal = income_goal::find($id);
            if (!$goal) {
                return [
                    'status' => 0,
                    'message' => 'La meta no existe',
                ];
            }

            $goal->target_amount = $targetAmount;
            $goal->frequency_months = $frequencyMonths;
            $goal->start_date = $range['start_date'];
            $goal->end_date = $range['end_date'];
            $goal->save();

            return [
                'status' => 1,
                'message' => 'Meta actualizada',
                'data' => $goal,
            ];
        } catch (\Exception $e) {
            info('IncomeGoal_Update error: '.$e->getMessage());

            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function IncomeGoal_Delete($id)
    {
        try {
            $goal = income_goal::find($id);
            if (!$goal) {
                return [
                    'status' => 0,
                    'message' => 'La meta no existe',
                ];
            }

            $goal->delete();

            return [
                'status' => 1,
                'message' => 'Meta eliminada',
                'data' => $goal,
            ];
        } catch (\Exception $e) {
            info('IncomeGoal_Delete error: '.$e->getMessage());

            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function IncomeGoal_Restore($id)
    {
        try {
            $goal = income_goal::withTrashed()->find($id);
            if (!$goal) {
                return [
                    'status' => 0,
                    'message' => 'La meta no existe',
                ];
            }

            $goal->restore();

            return [
                'status' => 1,
                'message' => 'Meta restaurada',
                'data' => $goal,
            ];
        } catch (\Exception $e) {
            info('IncomeGoal_Restore error: '.$e->getMessage());

            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
