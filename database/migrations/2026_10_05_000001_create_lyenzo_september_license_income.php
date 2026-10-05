<?php

use App\Http\Controllers\incomes_controller;
use App\Services\LicenseBillingCycleService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $billingDate = Carbon::create(2026, 9, 28)->startOfDay();
        $billingCycle = app(LicenseBillingCycleService::class);

        DB::transaction(function () use ($billingDate, $billingCycle): void {
            $clients = DB::table('clients')
                ->where('name', 'Lyenzo SAS')
                ->whereNull('lastname')
                ->where('active', 1)
                ->get();

            if ($clients->count() !== 1) {
                throw new RuntimeException('Expected exactly one active Lyenzo SAS client.');
            }

            $client = $clients->first();
            $license = DB::table('licenses')
                ->where('client_id', $client->id)
                ->where('name', '679-Lyenzo')
                ->where('active', 1)
                ->where('type', 1)
                ->where('recurrence_months', 1)
                ->where('billing_day', 28)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$license) {
                throw new RuntimeException('Expected active monthly license 679-Lyenzo for Lyenzo SAS.');
            }

            $issuedLicenseIds = $billingCycle->issuedLicenseIdsForPeriod(
                [$license->id],
                $billingDate
            );

            if (in_array((int) $license->id, $issuedLicenseIds, true)) {
                $existingIncomeId = DB::table('income_licenses as il')
                    ->join('incomes as i', 'i.id', '=', 'il.income_id')
                    ->where('il.license_id', $license->id)
                    ->whereIn('i.state', [2, 3, 4])
                    ->whereNull('i.deleted_at')
                    ->whereRaw(
                        'COALESCE(il.timely_payment, i.timely_payment) = ?',
                        [$billingDate->toDateString()]
                    )
                    ->orderByDesc('i.id')
                    ->value('i.id');

                $syncResponse = $billingCycle->syncIssuedIncome((int) $existingIncomeId);
                if ($syncResponse['status'] != 1) {
                    throw new RuntimeException($syncResponse['message']);
                }

                return;
            }

            $previousLine = DB::table('income_licenses as il')
                ->join('incomes as i', 'i.id', '=', 'il.income_id')
                ->where('il.license_id', $license->id)
                ->whereIn('i.state', [2, 3, 4])
                ->whereNull('i.deleted_at')
                ->whereRaw(
                    'COALESCE(il.timely_payment, i.timely_payment) < ?',
                    [$billingDate->toDateString()]
                )
                ->orderByRaw('COALESCE(il.timely_payment, i.timely_payment) DESC')
                ->orderByDesc('i.id')
                ->select('il.*')
                ->first();

            if (!$previousLine) {
                throw new RuntimeException('Cannot find a prior issued Lyenzo license line to snapshot.');
            }

            $value = (float) $previousLine->value;
            $taxValue = (float) ($previousLine->tax_value ?? 0);
            $lineTotal = $previousLine->total !== null
                ? (float) $previousLine->total
                : $value * (1 + $taxValue);
            $cutoffDate = $billingDate->copy()->addDays((int) $license->days_to_expire);

            $licenseLine = [
                'license_id' => (int) $license->id,
                'license_name' => $previousLine->license_name,
                'service_id' => (int) $previousLine->service_id,
                'service_name' => $previousLine->service_name,
                'recurrence_months' => (int) $license->recurrence_months,
                'value' => $value,
                'comission' => (float) ($previousLine->comission ?? 0),
                'employee_id' => $previousLine->employee_id,
                'employee_name' => $previousLine->employee_name,
                'tax_id' => $previousLine->tax_id,
                'tax_name' => $previousLine->tax_name,
                'tax_value' => $taxValue,
                'description' => $previousLine->description ?? '',
                'hours' => (int) ($previousLine->hours ?? 0),
                'total' => $lineTotal,
            ];

            $clientName = $client->name.($client->lastname ? ' '.$client->lastname : '');
            $creationResponse = app(incomes_controller::class)->Income_Createincome(
                2,
                (int) $client->id,
                $client->identification,
                $clientName,
                $billingDate,
                $cutoffDate,
                '',
                [$licenseLine]
            );

            if (($creationResponse['status'] ?? 0) != 1) {
                throw new RuntimeException($creationResponse['message'] ?? 'Failed to create the Lyenzo income.');
            }

            $incomeId = (int) data_get($creationResponse, 'data.income.id');
            if ($incomeId < 1) {
                throw new RuntimeException('Income creation returned no saved income ID.');
            }

            $pdfResponse = data_get($creationResponse, 'data.pdfResponse');
            if (($pdfResponse['status'] ?? 0) != 1) {
                throw new RuntimeException($pdfResponse['message'] ?? 'Failed to generate the Lyenzo income PDF.');
            }

            $syncResponse = $billingCycle->syncIssuedIncome($incomeId);
            if ($syncResponse['status'] != 1) {
                throw new RuntimeException($syncResponse['message']);
            }

            Log::info('Created the missing Lyenzo monthly income.', [
                'income_id' => $incomeId,
                'billing_period' => $billingDate->toDateString(),
            ]);
        });
    }

    public function down(): void
    {
        // Business income records must not be deleted by rolling back a migration.
    }
};
