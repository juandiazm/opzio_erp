<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\traits\incomes_trait;
use App\traits\outcomes_trait;
use App\traits\clients_trait;
use App\traits\licenses_trait;

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
}
