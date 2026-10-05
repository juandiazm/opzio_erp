<?php

namespace App\Services;

use App\Models\income;
use App\Models\license;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class LicenseBillingCycleService
{
    private const ISSUED_INCOME_STATES = [2, 3, 4];

    public function billingDateForMonth(int $year, int $month, int $billingDay): Carbon
    {
        if ($month < 1 || $month > 12 || !checkdate($month, 1, $year) || $billingDay < 1 || $billingDay > 31) {
            throw new InvalidArgumentException('Invalid billing month or day.');
        }

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();

        return $monthStart->day(min($billingDay, $monthStart->daysInMonth));
    }

    public function nextBillingDate(CarbonInterface $periodDate, int $recurrenceMonths, int $billingDay): Carbon
    {
        if ($recurrenceMonths < 1) {
            throw new InvalidArgumentException('License recurrence must be at least one month.');
        }

        $periodDate = Carbon::parse($periodDate)->startOfMonth()->addMonthsNoOverflow($recurrenceMonths);

        return $this->billingDateForMonth(
            $periodDate->year,
            $periodDate->month,
            $billingDay
        );
    }

    public function shouldBillOnDate(license $license, CarbonInterface $billingDate): bool
    {
        $billingDay = (int) $license->billing_day;
        $recurrenceMonths = max(1, (int) $license->recurrence_months);
        $billingDate = $this->billingDateForMonth(
            Carbon::parse($billingDate)->year,
            Carbon::parse($billingDate)->month,
            $billingDay
        );

        $anchors = [];
        $latestIssuedPeriod = $this->latestIncomePeriod((int) $license->id, false);
        if ($latestIssuedPeriod) {
            $anchors[] = $this->billingDateForMonth(
                $latestIssuedPeriod->year,
                $latestIssuedPeriod->month,
                $billingDay
            );
        }

        if ($license->last_billing_date) {
            $lastBillingDate = Carbon::parse($license->last_billing_date)->startOfDay();
            $anchors[] = $this->billingDateForMonth(
                $lastBillingDate->year,
                $lastBillingDate->month,
                $billingDay
            );
        }

        if ($license->last_payed_date) {
            $lastPayedDate = Carbon::parse($license->last_payed_date)->startOfDay();
            $storedLastPayed = $latestIssuedPeriod
                ? $this->normalizeAlignedStoredDate($lastPayedDate, $billingDay)
                : $this->normalizeStoredDate($lastPayedDate, $billingDay);

            if ($storedLastPayed) {
                $anchors[] = $storedLastPayed;
            }
        }

        if ($anchors === []) {
            if (!$license->next_billing_date) {
                return true;
            }

            $nextBillingDate = Carbon::parse($license->next_billing_date);
            $nextBillingDate = $this->billingDateForMonth(
                $nextBillingDate->year,
                $nextBillingDate->month,
                $billingDay
            );

            return $nextBillingDate->toDateString() === $billingDate->toDateString();
        }

        usort($anchors, static fn (Carbon $left, Carbon $right): int => $left <=> $right);
        $latestAnchor = end($anchors);

        if ($latestAnchor->greaterThanOrEqualTo($billingDate)) {
            return false;
        }

        $nextExpectedDate = $this->nextBillingDate($latestAnchor, $recurrenceMonths, $billingDay);

        if ($recurrenceMonths === 1) {
            return $nextExpectedDate->lessThanOrEqualTo($billingDate);
        }

        return $nextExpectedDate->toDateString() === $billingDate->toDateString();
    }

    public function issuedLicenseIdsForPeriod(iterable $licenseIds, CarbonInterface $periodDate): array
    {
        $licenseIds = collect($licenseIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($licenseIds->isEmpty()) {
            return [];
        }

        return DB::table('income_licenses as il')
            ->join('incomes as i', 'i.id', '=', 'il.income_id')
            ->whereIn('il.license_id', $licenseIds)
            ->whereIn('i.state', self::ISSUED_INCOME_STATES)
            ->whereNull('i.deleted_at')
            ->whereRaw(
                'COALESCE(il.timely_payment, i.timely_payment) = ?',
                [Carbon::parse($periodDate)->toDateString()]
            )
            ->distinct()
            ->pluck('il.license_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function syncIssuedIncome(int $incomeId): array
    {
        $income = income::with('income_licenses')->find($incomeId);
        if (!$income) {
            return ['status' => 0, 'message' => 'Income not found.', 'updated_licenses' => collect()];
        }

        if (!in_array((int) $income->state, self::ISSUED_INCOME_STATES, true)) {
            return ['status' => 1, 'message' => 'Income is not issued.', 'updated_licenses' => collect()];
        }

        $updatedLicenses = collect();
        $errors = [];

        foreach ($income->income_licenses->unique('license_id') as $incomeLicense) {
            $license = license::where('id', $incomeLicense->license_id)
                ->where('type', 1)
                ->first();

            if (!$license) {
                continue;
            }

            $billingDay = (int) $license->billing_day;
            if ($billingDay < 1 || $billingDay > 31) {
                $errors[] = 'License ' . $license->id . ' has an invalid billing day.';
                continue;
            }

            $effectivePeriod = $incomeLicense->timely_payment ?: $income->timely_payment;
            if (!$effectivePeriod) {
                $errors[] = 'Income ' . $income->id . ' has no billing period for license ' . $license->id . '.';
                Log::warning('Cannot sync license billing cycle without an income period.', [
                    'income_id' => $income->id,
                    'license_id' => $license->id,
                ]);
                continue;
            }

            $latestIssuedPeriod = $this->latestIncomePeriod((int) $license->id, false);
            $latestPaidPeriod = $this->latestIncomePeriod((int) $license->id, true);
            $storedLastBilling = $this->normalizeStoredDate($license->last_billing_date, $billingDay);
            $storedLastPayed = $latestPaidPeriod
                ? $this->normalizeAlignedStoredDate($license->last_payed_date, $billingDay)
                : $this->normalizeStoredDate($license->last_payed_date, $billingDay);

            $lastBillingDate = $this->latestDate([
                $this->normalizePeriod($latestIssuedPeriod, $billingDay),
                $this->normalizePeriod(Carbon::parse($effectivePeriod), $billingDay),
                $storedLastBilling,
            ]);
            $lastPayedDate = $this->latestDate([
                $this->normalizePeriod($latestPaidPeriod, $billingDay),
                $storedLastPayed,
            ]);

            $recurrenceMonths = max(1, (int) $license->recurrence_months);
            $nextBillingDate = $lastBillingDate
                ? $this->nextBillingDate($lastBillingDate, $recurrenceMonths, $billingDay)
                : ($lastPayedDate
                    ? $this->nextBillingDate($lastPayedDate, $recurrenceMonths, $billingDay)
                    : $this->normalizeStoredDate($license->next_billing_date, $billingDay));

            $remainingDays = $this->calculateRemainingDays(
                $lastPayedDate,
                $nextBillingDate,
                $recurrenceMonths,
                $billingDay
            );

            $nextValues = [
                'last_billing_date' => $lastBillingDate?->toDateString(),
                'last_payed_date' => $lastPayedDate?->toDateString(),
                'next_billing_date' => $nextBillingDate?->toDateString(),
                'remaining_days' => $remainingDays,
            ];

            $changed = false;
            foreach ($nextValues as $key => $value) {
                if ((string) $license->{$key} !== (string) $value) {
                    $license->{$key} = $value;
                    $changed = true;
                }
            }

            if ($changed) {
                $license->save();
                $updatedLicenses->push($license);
            }
        }

        return [
            'status' => $errors === [] ? 1 : 0,
            'message' => $errors === [] ? 'License billing dates synchronized.' : implode(' ', $errors),
            'updated_licenses' => $updatedLicenses,
        ];
    }

    public function syncPaidIncome(int $incomeId): array
    {
        $income = income::find($incomeId);
        if (!$income) {
            return ['status' => 0, 'message' => 'Income not found.', 'updated_licenses' => collect()];
        }

        if ((int) $income->payment_state !== 1) {
            return ['status' => 1, 'message' => 'Income is not fully paid.', 'updated_licenses' => collect()];
        }

        return $this->syncIssuedIncome($incomeId);
    }

    private function latestIncomePeriod(int $licenseId, bool $paidOnly): ?Carbon
    {
        $query = DB::table('income_licenses as il')
            ->join('incomes as i', 'i.id', '=', 'il.income_id')
            ->where('il.license_id', $licenseId)
            ->whereIn('i.state', self::ISSUED_INCOME_STATES)
            ->whereNull('i.deleted_at');

        if ($paidOnly) {
            $query->where('i.payment_state', 1);
        }

        $period = $query->max(DB::raw('COALESCE(il.timely_payment, i.timely_payment)'));

        return $period ? Carbon::parse($period)->startOfDay() : null;
    }

    private function normalizePeriod(?Carbon $period, int $billingDay): ?Carbon
    {
        if (!$period) {
            return null;
        }

        return $this->billingDateForMonth($period->year, $period->month, $billingDay);
    }

    private function normalizeStoredDate($date, int $billingDay): ?Carbon
    {
        if (!$date) {
            return null;
        }

        $date = Carbon::parse($date)->startOfDay();

        return $this->billingDateForMonth($date->year, $date->month, $billingDay);
    }

    private function normalizeAlignedStoredDate($date, int $billingDay): ?Carbon
    {
        if (!$date) {
            return null;
        }

        $date = Carbon::parse($date)->startOfDay();
        $normalized = $this->billingDateForMonth($date->year, $date->month, $billingDay);

        return $date->toDateString() === $normalized->toDateString() ? $normalized : null;
    }

    private function latestDate(array $dates): ?Carbon
    {
        $dates = array_values(array_filter($dates));
        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return end($dates);
    }

    private function calculateRemainingDays(
        ?Carbon $lastPayedDate,
        ?Carbon $nextBillingDate,
        int $recurrenceMonths,
        int $billingDay
    ): int {
        $referenceDate = $lastPayedDate
            ? $this->nextBillingDate($lastPayedDate, $recurrenceMonths, $billingDay)
            : $nextBillingDate;

        if (!$referenceDate) {
            return 0;
        }

        return (int) Carbon::now()->startOfDay()->diffInDays($referenceDate->startOfDay(), false);
    }
}
