<?php

namespace Tests\Feature;

use App\Models\income;
use App\Models\income_advance;
use App\Models\income_license;
use App\Models\license;
use App\Services\LicenseBillingCycleService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LicenseBillingCycleTest extends TestCase
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
        DB::setDefaultConnection('sqlite');

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('identification')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('electronic_invoice')->default(false);
            $table->timestamps();
        });

        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('client_id');
            $table->string('name');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('value')->default(0);
            $table->decimal('comission', 10, 2)->nullable();
            $table->unsignedTinyInteger('type')->default(1);
            $table->unsignedInteger('recurrence_months')->nullable();
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->unsignedTinyInteger('days_to_expire')->nullable();
            $table->dateTime('last_billing_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->date('last_payed_date')->nullable();
            $table->integer('remaining_days')->nullable();
            $table->string('user_key')->nullable();
            $table->string('password_key')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incomes', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('client_identification')->nullable();
            $table->string('client_name')->nullable();
            $table->date('timely_payment')->nullable();
            $table->date('cutoff_date')->nullable();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->decimal('total', 20, 2)->default(0);
            $table->tinyInteger('state')->default(0);
            $table->tinyInteger('payment_state')->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('bill_name')->nullable();
            $table->string('bill_final_value')->nullable();
            $table->string('siigo_invoice_id')->nullable();
            $table->string('siigo_document_id')->nullable();
            $table->string('siigo_invoice_url')->nullable();
            $table->boolean('quotation_totalize')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('income_licenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('license_id');
            $table->string('license_name')->nullable();
            $table->date('timely_payment')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->integer('recurrence_months')->nullable();
            $table->decimal('value', 20, 2)->nullable();
            $table->decimal('comission', 20, 2)->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->string('tax_name')->nullable();
            $table->decimal('tax_value', 10, 2)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_html')->nullable();
            $table->decimal('total', 20, 2)->nullable();
            $table->integer('hours')->default(0);
            $table->timestamps();
        });

        Schema::create('income_advances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->decimal('amount', 20, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('license_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('license_id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_income_created_late_does_not_hide_the_next_billing_period(): void
    {
        $license = $this->createLicense();
        $income = $this->createIncome($license, '2026-08-28', '2026-09-02', 3, 1);
        $this->createIncomeLicense($license, $income, null, 87562);

        $billingCycle = app(LicenseBillingCycleService::class);
        $nextPeriod = Carbon::parse('2026-09-28');

        $this->assertTrue($billingCycle->shouldBillOnDate($license->fresh(), $nextPeriod));
        $this->assertSame([], $billingCycle->issuedLicenseIdsForPeriod([$license->id], $nextPeriod));
    }

    public function test_an_income_for_the_exact_billing_period_prevents_a_duplicate(): void
    {
        $license = $this->createLicense();
        $income = $this->createIncome($license, '2026-09-28', '2026-10-02', 2, 0);
        $this->createIncomeLicense($license, $income, '2026-09-28', 87562);

        $billingCycle = app(LicenseBillingCycleService::class);
        $period = Carbon::parse('2026-09-28');

        $this->assertFalse($billingCycle->shouldBillOnDate($license->fresh(), $period));
        $this->assertSame([$license->id], $billingCycle->issuedLicenseIdsForPeriod([$license->id], $period));
    }

    public function test_billing_day_is_clamped_and_restored_after_a_short_month(): void
    {
        $billingCycle = app(LicenseBillingCycleService::class);

        $february = $billingCycle->billingDateForMonth(2026, 2, 31);
        $march = $billingCycle->nextBillingDate($february, 1, 31);

        $this->assertSame('2026-02-28', $february->toDateString());
        $this->assertSame('2026-03-31', $march->toDateString());
    }

    public function test_partial_advance_does_not_move_license_billing_dates(): void
    {
        $license = $this->createLicense();
        $income = $this->createIncome($license, '2026-09-28', '2026-10-28', 2, 0, 100);
        $this->createIncomeLicense($license, $income, '2026-09-28', 100);
        $advances = new class {
            use \App\traits\income_advances_trait;
        };

        $response = $advances->IncomeAdvance_Create(
            $income->id,
            40,
            '2026-10-03',
            'transfer'
        );

        $this->assertSame(1, $response['status']);
        $this->assertSame(2, (int) income::find($income->id)->state);
        $this->assertSame(0, (int) income::find($income->id)->payment_state);
        $this->assertSame('2026-08-28', license::find($license->id)->last_payed_date);
        $this->assertSame('2026-09-28', license::find($license->id)->next_billing_date);
    }

    public function test_final_advance_updates_cycle_from_invoice_period_not_payment_day(): void
    {
        $license = $this->createLicense();
        $income = $this->createIncome($license, '2026-09-28', '2026-10-28', 2, 0, 100);
        $this->createIncomeLicense($license, $income, '2026-09-28', 100);
        $advances = new class {
            use \App\traits\income_advances_trait;
        };

        $response = $advances->IncomeAdvance_Create(
            $income->id,
            100,
            '2026-10-03',
            'transfer'
        );

        $updatedLicense = license::find($license->id);

        $this->assertSame(1, $response['status']);
        $this->assertSame(3, (int) income::find($income->id)->state);
        $this->assertSame(1, (int) income::find($income->id)->payment_state);
        $this->assertSame('2026-10-03', income::find($income->id)->payment_date);
        $this->assertSame('2026-09-28', $updatedLicense->last_payed_date);
        $this->assertSame('2026-10-28', $updatedLicense->next_billing_date);
        $this->assertSame('2026-09-28', Carbon::parse($updatedLicense->last_billing_date)->toDateString());
    }

    public function test_editing_an_income_preserves_its_license_billing_period(): void
    {
        $license = $this->createLicense();
        $income = $this->createIncome($license, '2026-09-28', '2026-10-02', 2, 0, 100);
        $this->createIncomeLicense($license, $income, '2026-09-28', 100);
        $updater = new class {
            use \App\traits\incomes_trait;

            public function Client_GetClientById($clientId): array
            {
                return ['status' => 0, 'message' => 'Fixture client lookup'];
            }
        };

        $response = $updater->Income_UpdateIncome(
            $income->id,
            2,
            $license->client_id,
            'fixture',
            'Lyenzo SAS',
            '2026-09-28',
            '2026-10-28',
            '',
            null,
            null,
            [[
                'license_id' => $license->id,
                'license_name' => $license->name,
                'service_id' => $license->service_id,
                'service_name' => 'Tienda Virtual',
                'recurrence_months' => 1,
                'value' => 100,
                'comission' => 0,
                'employee_id' => null,
                'employee_name' => '',
                'tax_id' => null,
                'tax_name' => '',
                'tax_value' => 0,
                'description' => '',
                'total' => 100,
                'hours' => 0,
            ]]
        );

        $this->assertSame(1, $response['status']);
        $this->assertSame('2026-09-28', income_license::where('income_id', $income->id)->first()->timely_payment);
    }

    public function test_lyenzo_data_migration_creates_only_the_missing_period_once(): void
    {
        DB::table('clients')->insert([
            'id' => 25,
            'name' => 'Lyenzo SAS',
            'lastname' => null,
            'identification' => 'fixture',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $license = $this->createLicense([
            'id' => 63,
            'client_id' => 25,
            'name' => '679-Lyenzo',
            'last_billing_date' => '2026-06-28 08:00:05',
            'next_billing_date' => '2026-10-17',
            'last_payed_date' => '2026-09-17',
        ]);
        $previousIncome = $this->createIncome($license, '2026-08-28', '2026-09-02', 3, 1, 87562);
        $this->createIncomeLicense($license, $previousIncome, null, 87562);

        $creator = new class {
            public int $calls = 0;

            public function Income_Createincome(...$arguments): array
            {
                $this->calls++;
                [$state, $clientId, $identification, $clientName, $timelyPayment, $cutoffDate, $description, $lines] = $arguments;
                $total = collect($lines)->sum('total');
                $incomeId = DB::table('incomes')->insertGetId([
                    'unique_id' => 'MIGRATION-INCOME-'.$this->calls,
                    'client_id' => $clientId,
                    'client_identification' => $identification,
                    'client_name' => $clientName,
                    'timely_payment' => Carbon::parse($timelyPayment)->toDateString(),
                    'cutoff_date' => Carbon::parse($cutoffDate)->toDateString(),
                    'description' => $description,
                    'total' => $total,
                    'state' => $state,
                    'payment_state' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($lines as $line) {
                    DB::table('income_licenses')->insert([
                        'income_id' => $incomeId,
                        'license_id' => $line['license_id'],
                        'license_name' => $line['license_name'],
                        'timely_payment' => Carbon::parse($timelyPayment)->toDateString(),
                        'service_id' => $line['service_id'],
                        'service_name' => $line['service_name'],
                        'recurrence_months' => $line['recurrence_months'],
                        'value' => $line['value'],
                        'comission' => $line['comission'],
                        'employee_id' => $line['employee_id'],
                        'employee_name' => $line['employee_name'],
                        'tax_id' => $line['tax_id'],
                        'tax_name' => $line['tax_name'],
                        'tax_value' => $line['tax_value'],
                        'description' => $line['description'],
                        'total' => $line['total'],
                        'hours' => $line['hours'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return [
                    'status' => 1,
                    'data' => [
                        'income' => (object) ['id' => $incomeId],
                        'pdfResponse' => ['status' => 1],
                    ],
                ];
            }
        };
        app()->instance(\App\Http\Controllers\incomes_controller::class, $creator);

        $migrationPath = database_path('migrations/2026_10_05_000001_create_lyenzo_september_license_income.php');
        (require $migrationPath)->up();
        (require $migrationPath)->up();

        $createdIncome = income::whereDate('timely_payment', '2026-09-28')->first();
        $updatedLicense = license::find($license->id);

        $this->assertSame(1, $creator->calls);
        $this->assertSame(2, income::count());
        $this->assertSame('87562.00', number_format((float) $createdIncome->total, 2, '.', ''));
        $this->assertSame(2, (int) $createdIncome->state);
        $this->assertSame(0, (int) $createdIncome->payment_state);
        $this->assertSame('2026-10-28', $createdIncome->cutoff_date);
        $this->assertSame('2026-09-28', Carbon::parse($updatedLicense->last_billing_date)->toDateString());
        $this->assertSame('2026-08-28', $updatedLicense->last_payed_date);
        $this->assertSame('2026-10-28', $updatedLicense->next_billing_date);
    }

    private function createLicense(array $attributes = []): license
    {
        return license::forceCreate(array_merge([
            'unique_id' => 'LICENSE-63',
            'active' => 1,
            'client_id' => 25,
            'name' => '679-Lyenzo',
            'service_id' => 2,
            'value' => 87562,
            'comission' => 0,
            'type' => 1,
            'recurrence_months' => 1,
            'billing_day' => 28,
            'days_to_expire' => 30,
            'last_billing_date' => '2026-08-28 08:00:00',
            'next_billing_date' => '2026-09-28',
            'last_payed_date' => '2026-08-28',
            'remaining_days' => 0,
            'user_key' => 'USER-KEY-63',
            'password_key' => 'PASSWORD-KEY-63',
        ], $attributes));
    }

    private function createIncome(
        license $license,
        string $billingDate,
        string $createdAt,
        int $state,
        int $paymentState,
        float $total = 87562
    ): income {
        return income::forceCreate([
            'unique_id' => 'INCOME-'.uniqid(),
            'client_id' => $license->client_id,
            'client_identification' => 'fixture',
            'client_name' => 'Lyenzo SAS',
            'timely_payment' => $billingDate,
            'cutoff_date' => Carbon::parse($billingDate)->addDays(30)->toDateString(),
            'total' => $total,
            'state' => $state,
            'payment_state' => $paymentState,
            'payment_date' => $paymentState === 1 ? $createdAt : null,
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);
    }

    private function createIncomeLicense(
        license $license,
        income $income,
        ?string $lineBillingDate,
        float $total
    ): income_license {
        return income_license::forceCreate([
            'income_id' => $income->id,
            'license_id' => $license->id,
            'license_name' => $license->name,
            'timely_payment' => $lineBillingDate,
            'service_id' => $license->service_id,
            'service_name' => 'Tienda Virtual',
            'recurrence_months' => $license->recurrence_months,
            'value' => $total,
            'comission' => 0,
            'tax_value' => 0,
            'total' => $total,
            'hours' => 0,
        ]);
    }
}
