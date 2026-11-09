<?php

namespace Tests\Feature;

use App\Http\Controllers\incomes_controller;
use App\Models\income;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncomeListManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.env' => 'testing',
        ]);
        DB::purge('sqlite');
        Carbon::setTestNow('2026-11-09 12:00:00');

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('incomes', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id', 150);
            $table->unsignedBigInteger('client_id');
            $table->string('client_identification', 150);
            $table->string('client_name', 150);
            $table->date('timely_payment');
            $table->date('cutoff_date');
            $table->longText('description')->nullable();
            $table->decimal('total', 20, 2);
            $table->tinyInteger('state')->default(0);
            $table->tinyInteger('payment_state')->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('bill_name')->nullable();
            $table->string('bill_final_value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('clients')->insert([
            'id' => 1,
            'name' => 'Cliente de prueba',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_default_order_is_creation_date_descending_and_requested_column_can_be_sorted(): void
    {
        $this->createIncome('ING-RECENT', [
            'client_name' => 'Zeta',
            'total' => 200,
            'created_at' => '2026-11-08 12:00:00',
        ]);
        $this->createIncome('ING-OLDER', [
            'client_name' => 'Alfa',
            'total' => 100,
            'created_at' => '2026-11-07 12:00:00',
        ]);

        $defaultResult = $this->fetchPage();
        $sortedResult = $this->fetchPage([
            'sort_by' => 'total',
            'sort_direction' => 'asc',
        ]);

        $this->assertSame(1, $defaultResult['status']);
        $this->assertSame(['ING-RECENT', 'ING-OLDER'], $defaultResult['data']->pluck('unique_id')->all());
        $this->assertSame(['ING-OLDER', 'ING-RECENT'], $sortedResult['data']->pluck('unique_id')->all());
    }

    public function test_due_status_filters_only_open_approved_or_invoiced_incomes(): void
    {
        $this->createIncome('OVERDUE-APPROVED', ['state' => 2, 'cutoff_date' => '2026-11-08']);
        $this->createIncome('OVERDUE-INVOICED', ['state' => 4, 'cutoff_date' => '2026-11-01']);
        $this->createIncome('DUE-TODAY', ['state' => 2, 'cutoff_date' => '2026-11-09']);
        $this->createIncome('DUE-SEVEN', ['state' => 4, 'cutoff_date' => '2026-11-16']);
        $this->createIncome('DUE-EIGHT', ['state' => 2, 'cutoff_date' => '2026-11-17']);
        $this->createIncome('PAID', ['state' => 2, 'payment_state' => 1, 'cutoff_date' => '2026-11-08']);
        $this->createIncome('REJECTED', ['state' => 1, 'cutoff_date' => '2026-11-08']);
        $this->createIncome('QUOTE', ['state' => 0, 'cutoff_date' => '2026-11-08']);
        $this->createIncome('MARKED-PAID', ['state' => 3, 'cutoff_date' => '2026-11-08']);

        $overdueResult = $this->fetchPage(['due_status' => 'overdue']);
        $dueSoonResult = $this->fetchPage(['due_status' => 'due_soon']);

        $this->assertSame(1, $overdueResult['status']);
        $this->assertEqualsCanonicalizing(
            ['OVERDUE-APPROVED', 'OVERDUE-INVOICED'],
            $overdueResult['data']->pluck('unique_id')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['DUE-TODAY', 'DUE-SEVEN'],
            $dueSoonResult['data']->pluck('unique_id')->all()
        );
    }

    public function test_status_filter_accepts_multiple_states_and_all_states(): void
    {
        $this->createIncome('QUOTATION', ['state' => 0]);
        $this->createIncome('REJECTED', ['state' => 1]);
        $this->createIncome('APPROVED', ['state' => 2]);
        $this->createIncome('PAID', ['state' => 3]);
        $this->createIncome('INVOICED', ['state' => 4]);

        $selectedResult = $this->fetchPage(['states' => ['2', '4']]);
        $allResult = $this->fetchPage(['states' => ['0', '1', '2', '3', '4']]);

        $this->assertSame(['INVOICED', 'APPROVED'], $selectedResult['data']->pluck('unique_id')->all());
        $this->assertSame(5, $allResult['pagination']['total']);
    }

    public function test_aging_sort_places_paid_incomes_at_the_edge_selected_by_direction(): void
    {
        $this->createIncome('OVERDUE', ['state' => 2, 'cutoff_date' => '2026-11-08']);
        $this->createIncome('UPCOMING', ['state' => 4, 'cutoff_date' => '2026-11-16']);
        $this->createIncome('PAID-BY-PAYMENT-STATE', [
            'state' => 2,
            'payment_state' => 1,
            'cutoff_date' => '2026-11-10',
        ]);
        $this->createIncome('PAID-BY-INCOME-STATE', [
            'state' => 3,
            'cutoff_date' => '2026-11-15',
        ]);

        $ascendingResult = $this->fetchPage([
            'sort_by' => 'aging',
            'sort_direction' => 'asc',
        ]);
        $descendingResult = $this->fetchPage([
            'sort_by' => 'aging',
            'sort_direction' => 'desc',
        ]);

        $this->assertSame(
            ['OVERDUE', 'UPCOMING', 'PAID-BY-PAYMENT-STATE', 'PAID-BY-INCOME-STATE'],
            $ascendingResult['data']->pluck('unique_id')->all()
        );
        $this->assertSame(
            ['PAID-BY-INCOME-STATE', 'PAID-BY-PAYMENT-STATE', 'UPCOMING', 'OVERDUE'],
            $descendingResult['data']->pluck('unique_id')->all()
        );
    }

    public function test_days_until_cutoff_is_zero_for_due_or_overdue_incomes(): void
    {
        $income = new income();
        $income->cutoff_date = '2026-11-16';
        $this->assertSame(7, $income->days_until_cutoff);

        $income->cutoff_date = '2026-11-08';
        $this->assertSame(0, $income->days_until_cutoff);
    }

    private function fetchPage(array $filters = []): array
    {
        return (new incomes_controller())->Income_GetPage(
            ['page' => 1, 'per_page' => 50],
            '',
            null,
            false,
            $filters['due_status'] ?? 'all',
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_direction'] ?? 'desc',
            $filters['states'] ?? null
        );
    }

    private function createIncome(string $uniqueId, array $attributes = []): void
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::table('incomes')->insert(array_merge([
            'unique_id' => $uniqueId,
            'client_id' => 1,
            'client_identification' => '123456789',
            'client_name' => 'Cliente de prueba',
            'timely_payment' => '2026-11-05',
            'cutoff_date' => '2026-11-30',
            'total' => 100,
            'state' => 2,
            'payment_state' => 0,
            'payment_date' => null,
            'payment_reference' => null,
            'bill_name' => null,
            'bill_final_value' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }
}
