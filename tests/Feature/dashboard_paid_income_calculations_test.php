<?php

namespace Tests\Feature;

use App\Http\Controllers\dashboard_controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class dashboard_paid_income_calculations_test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('state');
            $table->decimal('total', 20, 2);
            $table->unsignedTinyInteger('payment_state')->default(0);
            $table->date('payment_date')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('client_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('income_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->decimal('amount', 20, 2);
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('income_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedTinyInteger('payment_state')->default(0);
            $table->decimal('total', 20, 2);
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('type');
            $table->softDeletes();
        });

        Schema::create('income_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('license_id');
            $table->decimal('total', 20, 2);
            $table->unsignedInteger('recurrence_months')->nullable();
        });

        $firstIncome = DB::table('incomes')->insertGetId([
            'state' => 3,
            'total' => 1000,
            'payment_state' => 1,
            'payment_date' => '2026-01-15',
            'client_id' => 1,
            'client_name' => 'Cliente Uno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondIncome = DB::table('incomes')->insertGetId([
            'state' => 2,
            'total' => 1000,
            'payment_state' => 0,
            'payment_date' => null,
            'client_id' => 2,
            'client_name' => 'Cliente Dos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('income_payments')->insert([
            'income_id' => $firstIncome,
            'payment_state' => 1,
            'total' => 400,
            'payment_date' => '2026-01-15 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('income_advances')->insert([
            'income_id' => $secondIncome,
            'amount' => 200,
            'payment_date' => '2026-01-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('income_payments')->insert([
            'income_id' => $secondIncome,
            'payment_state' => 1,
            'total' => 300,
            'payment_date' => '2026-01-21 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $type2License = DB::table('licenses')->insertGetId(['type' => 2]);
        $type1License = DB::table('licenses')->insertGetId(['type' => 1]);
        DB::table('income_licenses')->insert([
            ['income_id' => $firstIncome, 'license_id' => $type2License, 'total' => 600, 'recurrence_months' => 1],
            ['income_id' => $firstIncome, 'license_id' => $type1License, 'total' => 400, 'recurrence_months' => 1],
            ['income_id' => $secondIncome, 'license_id' => $type2License, 'total' => 500, 'recurrence_months' => 1],
            ['income_id' => $secondIncome, 'license_id' => $type1License, 'total' => 500, 'recurrence_months' => 1],
        ]);
    }

    public function test_dashboard_totals_use_confirmed_payments_and_paid_advances(): void
    {
        $controller = new dashboard_controller();

        $month = $controller->Income_StatisticGetIncomeValuesByMonth('2026-01');
        $range = $controller->Income_StatisticGetIncomesByMonthRange('2026-01', '2026-02');
        $sales = $controller->Income_StatisticGetSalesByMonthRange('2026-01', '2026-01');
        $clients = $controller->Income_StatisticGetIncomesByClientDateRange('2026-01', '2026-01');

        $this->assertSame('900', $month['data']['current_month']);
        $this->assertEquals(900, $range['data']['incomes_total']);
        $this->assertEquals([900, 0], $range['data']['incomes_by_month']->all());
        $this->assertEquals(490, $sales['data']['incomes_total']);
        $this->assertEquals(490, $sales['data']['incomes_by_month']->first());
        $this->assertEquals(900, $clients['data']['incomes_total']);
        $this->assertEquals([500, 400], $clients['data']['incomes_by_client']);
    }
}
