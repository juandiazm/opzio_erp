<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\incomes_trait;
use App\traits\outcomes_trait;
use App\traits\clients_trait;
use App\traits\licenses_trait;
use App\Models\income_goal;
use Carbon\Carbon;

class dashboard_controller extends Controller
{
    use 
    incomes_trait
    ,outcomes_trait
    ,clients_trait
    ,licenses_trait
    ;
    public function get_income_outcome_values_by_month(Request $request)
    {
        $Incomes = $this->Income_StatisticGetIncomeValuesByMonth($request->date);
        $Incomes = $Incomes['data'];
        $Outcomes = $this->Outcome_StatisticGetOutcomeValuesByMonth($request->date);
        $Outcomes = $Outcomes['data'];
        ////////////////////////////
        $Response = [
            'status' => 1,
            'data' => [
                'incomes' => $Incomes,
                'outcomes' => $Outcomes
            ]
        ];
        return $Response;
    }
    public function get_incomes_by_status(Request $request)
    {
        $Incomes = $this->Income_StatisticGetIncomesByStatus($request->status);
        return $Incomes;
    }
    public function get_income_goal_progress(Request $request)
    {
        try {
            $referenceDate = Carbon::today();
            $goals = income_goal::query()
                ->orderBy('start_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $progress = $goals->map(function ($goal) use ($referenceDate) {
                $period = $this->get_income_goal_comparison_period($goal, $referenceDate);
                $targetAmount = (float) $goal->target_amount;
                $actualAmount = 0;

                if ($period['start_date'] !== null) {
                    $incomeResponse = $this->Income_StatisticGetIncomesByMonthRange(
                        $period['start_date'],
                        $period['end_date']
                    );
                    $actualAmount = (float) ($incomeResponse['data']['incomes_total'] ?? 0);
                }

                $completionPercentage = $targetAmount > 0
                    ? round(($actualAmount / $targetAmount) * 100, 2)
                    : 0;
                $remainingAmount = max($targetAmount - $actualAmount, 0);

                return [
                    'id' => $goal->id,
                    'target_amount' => $targetAmount,
                    'target_amount_string' => number_format($targetAmount, 0, ',', '.'),
                    'frequency_months' => (int) $goal->frequency_months,
                    'frequency_label' => $this->get_income_goal_frequency_label($goal->frequency_months),
                    'goal_start_date' => $goal->start_date ? Carbon::parse($goal->start_date)->format('Y-m-d') : null,
                    'goal_end_date' => $goal->end_date ? Carbon::parse($goal->end_date)->format('Y-m-d') : null,
                    'comparison_start_date' => $period['start_date'],
                    'comparison_end_date' => $period['end_date'],
                    'actual_amount' => $actualAmount,
                    'actual_amount_string' => number_format($actualAmount, 0, ',', '.'),
                    'remaining_amount' => $remainingAmount,
                    'remaining_amount_string' => number_format($remainingAmount, 0, ',', '.'),
                    'completion_percentage' => $completionPercentage,
                    'completion_percentage_string' => number_format($completionPercentage, 2, ',', '.').'%',
                    'progress_percentage' => min(max($completionPercentage, 0), 100),
                    'status' => $period['status'],
                    'status_label' => $period['status_label'],
                ];
            })->values();

            return [
                'status' => 1,
                'data' => [
                    'reference_date' => $referenceDate->format('Y-m-d'),
                    'goals' => $progress,
                ],
            ];
        } catch (\Exception $e) {
            info('Dashboard_GetIncomeGoalProgress error: '.$e->getMessage());

            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
    public function get_incomes_by_recurrence_range(Request $request)
    {
        return $this->Income_StatisticGetIncomesByRecurrenceRange($request->date_from, $request->date_to);
    }
    public function get_active_clients_and_licenses(Request $request)
    {
        $Clients = $this->Client_StatisticGetActiveClients();
        $Clients = $Clients['data'];
        $Licenses = $this->License_StatisticGetActiveLicenses();
        $Licenses = $Licenses['data'];
        ////////////////////////////
        $Response = [
            'status' => 1,
            'data' => [
                'clients' => $Clients,
                'licenses' => $Licenses
            ]
        ];
        return $Response;
    }
    public function get_incomes_outcomes_by_month_range(Request $request)
    {
        $Incomes = $this->Income_StatisticGetIncomesByMonthRange($request->date_from, $request->date_to);
        $Incomes = $Incomes['data'];
        $Outcomes = $this->Outcome_StatisticGetOutcomesByMonthRange($request->date_from, $request->date_to);
        $Outcomes = $Outcomes['data'];
        $Balance = [
            'total' => $Incomes['incomes_total'] - $Outcomes['outcomes_total'],
            'total_string' => number_format($Incomes['incomes_total'] - $Outcomes['outcomes_total'], 0,',','.')
        ];
        ////////////////////////////
        $Response = [
            'status' => 1,
            'data' => [
                'incomes' => $Incomes,
                'outcomes' => $Outcomes,
                'balance' => $Balance
            ]
        ];
        return $Response;
    }
    public function get_client_licences_dues(Request $request)
    {
        $Licences = $this->License_StatisticGetLicencesDues();
        return $Licences;
    }
    public function get_new_clients_by_date_range(Request $request)
    {
        $Clients = $this->Client_StatisticGetNewClientsByDateRange($request->date_from, $request->date_to);
        return $Clients;
    }
    public function get_sales_by_month_range(Request $request)
    {
        $Sales = $this->Income_StatisticGetSalesByMonthRange($request->date_from, $request->date_to);
        return $Sales;
    }
    public function get_incomes_by_client_date_range(Request $request)
    {
        $IncomesByClient = $this->Income_StatisticGetIncomesByClientDateRange($request->date_from, $request->date_to);
        return $IncomesByClient;
    }

    private function get_income_goal_comparison_period($goal, Carbon $referenceDate)
    {
        if (!$goal->start_date || !$goal->end_date || (int) $goal->frequency_months < 1) {
            return [
                'start_date' => null,
                'end_date' => null,
                'status' => 'missing_range',
                'status_label' => 'Sin rango',
            ];
        }

        $goalStart = Carbon::parse($goal->start_date)->startOfMonth();
        $goalEnd = Carbon::parse($goal->end_date)->endOfMonth();
        $frequencyMonths = (int) $goal->frequency_months;
        $totalMonths = (($goalEnd->year - $goalStart->year) * 12) + ($goalEnd->month - $goalStart->month) + 1;
        $periodCount = max((int) ceil($totalMonths / $frequencyMonths), 1);
        $referenceMonth = $referenceDate->copy()->startOfMonth();

        if ($referenceMonth->lessThan($goalStart)) {
            $periodIndex = 0;
            $status = 'upcoming';
            $statusLabel = 'Próxima';
        } elseif ($referenceMonth->greaterThan($goalEnd)) {
            $periodIndex = $periodCount - 1;
            $status = 'finished';
            $statusLabel = 'Finalizada';
        } else {
            $elapsedMonths = (($referenceMonth->year - $goalStart->year) * 12) + ($referenceMonth->month - $goalStart->month);
            $periodIndex = min((int) floor($elapsedMonths / $frequencyMonths), $periodCount - 1);
            $status = 'in_progress';
            $statusLabel = 'En curso';
        }

        $periodStart = $goalStart->copy()->addMonths($periodIndex * $frequencyMonths);
        $periodEnd = $periodStart->copy()->addMonths($frequencyMonths)->subDay()->endOfDay();
        if ($periodEnd->greaterThan($goalEnd)) {
            $periodEnd = $goalEnd->copy();
        }

        return [
            'start_date' => $periodStart->format('Y-m-d'),
            'end_date' => $periodEnd->format('Y-m-d'),
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }

    private function get_income_goal_frequency_label($frequencyMonths)
    {
        $frequencyMonths = (int) $frequencyMonths;

        if ($frequencyMonths === 1) {
            return 'Mensual';
        }

        if ($frequencyMonths === 12) {
            return 'Anual';
        }

        return 'Cada '.$frequencyMonths.' meses';
    }
}
