<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\income;
use App\Models\income_license;
use App\Models\license;
use App\Models\license_notification;
use App\Models\notification_tag;
use App\Models\sms_log;
use App\Http\Controllers\incomes_controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncomePaymentReminderFakeMessageList
{
    public function create($to, array $options)
    {
        return (object) [
            'sid' => 'SM-INCOME-REMINDER-001',
            'status' => 'queued',
            'errorCode' => null,
            'errorMessage' => null,
            'dateSent' => null,
        ];
    }
}

class IncomePaymentReminderFakeTwilioClient
{
    public function __get($name)
    {
        if ($name === 'messages') {
            return new IncomePaymentReminderFakeMessageList();
        }

        throw new \RuntimeException('Unknown fake Twilio property: '.$name);
    }
}

class IncomePaymentReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.env' => 'testing',
            'app.url' => 'https://erp.example.test',
        ]);
        DB::purge('sqlite');

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->boolean('active')->default(true);
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('income_licenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('license_id');
            $table->string('license_name')->nullable();
            $table->string('service_name')->nullable();
            $table->decimal('value', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('license_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('license_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('channels')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('notification_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('notification_contact_tag', function (Blueprint $table): void {
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();
            $table->primary(['contact_id', 'tag_id']);
        });
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('to');
            $table->longText('body');
            $table->string('twilio_sid')->nullable();
            $table->string('twilio_status')->nullable();
            $table->unsignedInteger('twilio_error_code')->nullable();
            $table->text('twilio_error_message')->nullable();
            $table->dateTime('twilio_checked_at')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('send_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('notification_batch')->nullable();
            $table->unsignedBigInteger('resend_of_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_payment_reminder_uses_tagged_license_number_and_manual_sms_number(): void
    {
        $service = new class extends incomes_controller {
            private $fakeTwilioClient;

            public function __construct()
            {
                parent::__construct();
                $this->fakeTwilioClient = new IncomePaymentReminderFakeTwilioClient();
            }

            protected function TwilioSMS_CreateClient()
            {
                return $this->fakeTwilioClient;
            }
        };
        $client = client::forceCreate([
            'name' => 'Cliente Recordatorio',
            'lastname' => 'Principal',
            'phone' => '3000000001',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia',
            'active' => true,
        ]);
        $income = income::forceCreate([
            'unique_id' => 'INCOME-REMINDER-001',
            'client_id' => $client->id,
            'client_identification' => '9001',
            'client_name' => 'Cliente Recordatorio',
            'timely_payment' => '2026-09-01',
            'cutoff_date' => '2026-09-10',
            'total' => 125000,
            'state' => 2,
            'payment_state' => 0,
        ]);
        income_license::forceCreate([
            'income_id' => $income->id,
            'license_id' => $license->id,
            'license_name' => 'Licencia',
            'service_name' => 'Servicio',
            'value' => 125000,
            'total' => 125000,
        ]);
        $tag = notification_tag::create(['name' => 'Cobranza', 'slug' => 'cobranza']);
        $contact = license_notification::forceCreate([
            'client_id' => $client->id,
            'license_id' => $license->id,
            'name' => 'Contacto Licencia',
            'phone' => '3000000002',
            'channels' => ['sms'],
            'active' => true,
            'position' => 1,
        ]);
        $contact->tags()->attach($tag->id);

        $recipients = $service->Income_GetPaymentReminderRecipients($income->id);
        $sent = $service->Income_SendPaymentReminder($income->id, '3000000003', 'sms');

        $this->assertSame(1, $recipients['status']);
        $this->assertSame('+573000000002', $recipients['recipients'][0]['phone']);
        $this->assertSame(1, $sent['status']);
        $this->assertSame('+573000000003', sms_log::first()->to);
        $this->assertStringContainsString($income->payment_link, sms_log::first()->body);

        $income->payment_state = 1;
        $income->save();
        $unavailable = $service->Income_GetPaymentReminderRecipients($income->id);
        $this->assertSame(0, $unavailable['status']);
    }
}
