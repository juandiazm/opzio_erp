<?php

namespace Tests\Feature;

use App\Http\Controllers\dashboard_controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class dashboard_income_goals_test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('income_goals', function (Blueprint $table) {
            $table->id();
            $table->decimal('target_amount', 20, 2);
            $table->unsignedInteger('frequency_months');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('state');
            $table->decimal('total', 20, 2);
            $table->date('payment_date')->nullable();
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

        Carbon::setTestNow('2026-08-18');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_compares_each_goal_with_its_configured_period(): void
    {
        $goal = DB::table('income_goals')->insertGetId([
            'target_amount' => 2000,
            'frequency_months' => 3,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paidIncome = DB::table('incomes')->insertGetId([
            'state' => 3,
            'total' => 1000,
            'payment_date' => '2026-07-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $incomeWithAdvance = DB::table('incomes')->insertGetId([
            'state' => 2,
            'total' => 900,
            'payment_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('income_advances')->insert([
            'income_id' => $incomeWithAdvance,
            'amount' => 300,
            'payment_date' => '2026-07-12',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rejectedIncome = DB::table('incomes')->insertGetId([
            'state' => 1,
            'total' => 700,
            'payment_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('income_advances')->insert([
            'income_id' => $rejectedIncome,
            'amount' => 500,
            'payment_date' => '2026-07-13',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('incomes')->insert([
            'state' => 3,
            'total' => 500,
            'payment_date' => '2026-10-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new dashboard_controller)->get_income_goal_progress(new Request());
        $result = $response['data']['goals'][0];

        $this->assertSame(1, $response['status']);
        $this->assertSame($goal, $result['id']);
        $this->assertSame('2026-07-01', $result['comparison_start_date']);
        $this->assertSame('2026-09-30', $result['comparison_end_date']);
        $this->assertSame(1300.0, $result['actual_amount']);
        $this->assertSame(65.0, $result['completion_percentage']);
    }
}