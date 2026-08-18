<?php

namespace Tests\Feature;

use App\Http\Controllers\dashboard_controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class dashboard_income_recurrence_test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('type')->default(1);
            $table->unsignedInteger('recurrence_months')->nullable();
            $table->decimal('value', 20, 2);
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

        Schema::create('income_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('license_id');
            $table->unsignedInteger('recurrence_months')->nullable();
            $table->decimal('total', 20, 2);
            $table->timestamps();
        });

        Schema::create('income_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->decimal('amount', 20, 2);
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function test_recurrence_projection_groups_active_licenses_and_paid_income(): void
    {
        $monthlyLicenseIds = [];
        for ($index = 0; $index < 5; $index++) {
            $monthlyLicenseIds[] = DB::table('licenses')->insertGetId([
                'active' => 1,
                'type' => 1,
                'recurrence_months' => 1,
                'value' => 800,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $quarterlyLicenseId = DB::table('licenses')->insertGetId([
            'active' => 1,
            'type' => 1,
            'recurrence_months' => 3,
            'value' => 2000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paidIncomeId = DB::table('incomes')->insertGetId([
            'state' => 3,
            'total' => 3600,
            'payment_date' => '2026-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('income_licenses')->insert([
            [
                'income_id' => $paidIncomeId,
                'license_id' => $monthlyLicenseIds[0],
                'recurrence_months' => 1,
                'total' => 800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'income_id' => $paidIncomeId,
                'license_id' => $monthlyLicenseIds[1],
                'recurrence_months' => 1,
                'total' => 800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'income_id' => $paidIncomeId,
                'license_id' => $quarterlyLicenseId,
                'recurrence_months' => 3,
                'total' => 2000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $advanceIncomeId = DB::table('incomes')->insertGetId([
            'state' => 2,
            'total' => 800,
            'payment_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('income_licenses')->insert([
            'income_id' => $advanceIncomeId,
            'license_id' => $monthlyLicenseIds[2],
            'recurrence_months' => 1,
            'total' => 800,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('income_advances')->insert([
            'income_id' => $advanceIncomeId,
            'amount' => 400,
            'payment_date' => '2026-03-05',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new dashboard_controller)->get_incomes_by_recurrence_range(new Request([
            'date_from' => '2026-01',
            'date_to' => '2026-03',
        ]));
        $details = collect($response['data']['recurrence_details'])->keyBy('recurrence_months');

        $this->assertSame(1, $response['status']);
        $this->assertEquals(12000, $details[1]['projected_amount']);
        $this->assertEquals(2000, $details[1]['paid_amount']);
        $this->assertSame(5, $details[1]['active_license_count']);
        $this->assertSame(2, $details[1]['paid_income_count']);
        $this->assertEquals(2000, $details[3]['projected_amount']);
        $this->assertEquals(2000, $details[3]['paid_amount']);
        $this->assertSame(1, $details[3]['paid_income_count']);
        $this->assertEquals(14000, $response['data']['projected_total']);
        $this->assertEquals(4000, $response['data']['paid_total']);
    }
}