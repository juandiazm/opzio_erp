<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\income;
use App\Models\income_license;
use App\Models\income_payment;
use App\Models\license_notification;
use App\traits\income_payments_trait;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentWebhookIdempotencyTest extends TestCase
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

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('identification')->nullable();
            $table->tinyInteger('identification_type')->default(0);
            $table->boolean('active')->default(1);
            $table->boolean('verified')->default(1);
            $table->timestamps();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100);
            $table->unsignedBigInteger('client_id');
            $table->string('client_identification')->nullable();
            $table->string('client_name');
            $table->date('timely_payment')->nullable();
            $table->date('cutoff_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total', 20, 2)->default(0);
            $table->tinyInteger('state')->default(0);
            $table->tinyInteger('payment_state')->default(0);
            $table->dateTime('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('bill_name')->nullable();
            $table->decimal('bill_final_value', 20, 2)->nullable();
            $table->string('siigo_invoice_id')->nullable();
            $table->string('siigo_document_id')->nullable();
            $table->text('siigo_invoice_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('income_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->string('unique_id', 50);
            $table->string('payment_method', 50);
            $table->string('transaction_id', 100)->nullable();
            $table->string('currency', 10);
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount', 20, 2)->default(0);
            $table->decimal('tax', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->tinyInteger('payment_state')->default(0);
            $table->string('payment_reference', 100)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->longText('payment_response')->nullable();
            $table->string('payment_message', 100)->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->unsignedBigInteger('client_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('income_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('license_id');
            $table->timestamps();
        });

        Schema::create('license_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('license_id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_repeated_bold_approved_webhook_notifies_once(): void
    {
        [$income, $payment] = $this->createPaymentFixture();
        $processor = $this->processor();
        $webhook = [
            'type' => 'SALE_APPROVED',
            'data' => [
                'metadata' => ['reference' => $payment->unique_id],
                'payment_id' => 'BOLD-PAYMENT-1',
            ],
        ];

        $firstResponse = $processor->IncomePayment_FinishedBoldPayment('{}', 'signature', $webhook);
        $firstPaymentDate = income_payment::find($payment->id)->payment_date;
        $secondResponse = $processor->IncomePayment_FinishedBoldPayment('{}', 'signature', $webhook);
        $secondPaymentDate = income_payment::find($payment->id)->payment_date;

        $this->assertSame(1, $firstResponse['status']);
        $this->assertSame(1, $secondResponse['status']);
        $this->assertSame(2, $processor->mailCalls);
        $this->assertSame(1, income_payment::find($payment->id)->payment_state);
        $this->assertSame($firstPaymentDate, $secondPaymentDate);
    }

    public function test_repeated_wompi_approved_webhook_notifies_once(): void
    {
        [, $payment] = $this->createPaymentFixture();
        $processor = $this->processor();
        $transaction = [
            'id' => 'WOMPI-PAYMENT-1',
            'status' => 'APPROVED',
            'status_message' => 'Aprobada',
        ];
        $security = [
            'signature' => [
                'properties' => ['transaction.id', 'transaction.status'],
                'checksum' => hash('sha256', $transaction['id'] . $transaction['status'] . 'timestamp' . 'events-key'),
            ],
            'timestamp' => 'timestamp',
        ];

        $firstResponse = $processor->IncomePayment_FinishedWompiPayment($security, $payment->unique_id, $transaction);
        $secondResponse = $processor->IncomePayment_FinishedWompiPayment($security, $payment->unique_id, $transaction);

        $this->assertSame(1, $firstResponse['status']);
        $this->assertSame(1, $secondResponse['status']);
        $this->assertSame(2, $processor->mailCalls);
    }

    public function test_repeated_manual_payment_does_not_send_thanks_twice(): void
    {
        [$income] = $this->createPaymentFixture();
        $processor = $this->processor();

        $processor->Income_UpdateIncomePaymentData(
            $income->id,
            1,
            Carbon::now(),
            'MANUAL-PAYMENT-1',
            'INVOICE-1',
            100,
            true
        );
        $processor->Income_UpdateIncomePaymentData(
            $income->id,
            1,
            Carbon::now(),
            'MANUAL-PAYMENT-1',
            'INVOICE-1',
            100,
            true
        );

        $this->assertSame(1, $processor->mailCalls);
        $this->assertSame(1, income::find($income->id)->payment_state);
    }

    private function createPaymentFixture(): array
    {
        $client = client::forceCreate([
            'name' => 'Cliente de prueba',
            'email' => 'cliente@example.test',
            'identification' => '123456',
            'active' => 1,
            'verified' => 1,
        ]);
        $income = income::forceCreate([
            'unique_id' => 'INCOME-TEST-1',
            'client_id' => $client->id,
            'client_identification' => $client->identification,
            'client_name' => $client->name,
            'total' => 100,
            'state' => 2,
            'payment_state' => 0,
            'bill_name' => null,
        ]);
        income_license::forceCreate([
            'income_id' => $income->id,
            'license_id' => 1,
        ]);
        license_notification::forceCreate([
            'license_id' => 1,
            'email' => 'cliente@example.test',
        ]);
        $payment = new income_payment();
        $payment->income_id = $income->id;
        $payment->unique_id = 'PAYMENT-TEST-1';
        $payment->payment_method = 'bold';
        $payment->currency = 'COP';
        $payment->subtotal = 100;
        $payment->total = 100;
        $payment->payment_state = 0;
        $payment->save();

        return [$income, $payment];
    }

    private function processor()
    {
        return new class {
            use income_payments_trait;

            public int $mailCalls = 0;

            public function Bold_ValidateWebhookSignature($body, $signature): bool
            {
                return true;
            }

            public function Wompi_GetWompiKey($key): array
            {
                return [
                    'status' => 1,
                    'data' => ['value' => 'events-key'],
                ];
            }

            public function License_UpdateBillingDataByIds($licenses): array
            {
                return ['status' => 1];
            }

            public function SendMail($mailData, $mails, $view, $viewData, $files, $uniqueId = null, $mailer = null, $from = null, $replyTo = null): array
            {
                $this->mailCalls++;
                return ['status' => 1, 'message' => 'ok'];
            }
        };
    }
}
