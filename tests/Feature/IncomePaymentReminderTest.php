<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\income;
use App\Models\income_license;
use App\Models\license;
use App\Models\license_notification;
use App\Models\notification_tag;
use App\Models\sms_log;
use App\Models\whatsapp_message;
use App\Http\Controllers\incomes_controller;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncomePaymentReminderFakeMessageList
{
    public function __construct(private $client)
    {
    }

    public function create($to, array $options)
    {
        $this->client->lastCreated = ['to' => $to, 'options' => $options];
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
    public array $lastCreated = [];

    private $messages;

    public function __construct()
    {
        $this->messages = new IncomePaymentReminderFakeMessageList($this);
    }

    public function __get($name)
    {
        if ($name === 'messages') {
            return $this->messages;
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
            'services.twilio.sid' => 'AC00000000000000000000000000000000',
            'services.twilio.token' => 'test-token',
            'services.twilio.whatsapp.from' => 'whatsapp:+573145433746',
            'services.twilio.whatsapp.messaging_service_sid' => null,
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
        Schema::create('whatsapp_conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('phone');
            $table->string('business_address');
            $table->string('wa_id')->nullable();
            $table->string('display_name')->nullable();
            $table->string('profile_name')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->text('last_message_preview')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('last_inbound_at')->nullable();
            $table->dateTime('window_expires_at')->nullable();
            $table->dateTime('last_read_at')->nullable();
            $table->string('last_inbound_sid')->nullable();
            $table->string('last_outbound_sid')->nullable();
            $table->timestamps();
            $table->unique(['phone', 'business_address']);
        });
        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('twilio_sid')->nullable()->unique();
            $table->string('direction');
            $table->string('from');
            $table->string('to');
            $table->longText('body')->nullable();
            $table->string('message_type')->default('text');
            $table->json('media')->nullable();
            $table->string('content_sid')->nullable();
            $table->json('content_variables')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('send_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('status_updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->boolean('ai_generated')->default(false);
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

            protected function TwilioWhatsApp_CreateClient()
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

        $contact->channels = ['sms', 'whatsapp'];
        $contact->save();
        $whatsappSent = $service->Income_SendPaymentReminder($income->id, '3000000002', 'whatsapp');
        $this->assertSame(1, $whatsappSent['status']);
        $this->assertSame('Contacto Licencia', whatsapp_message::first()->content_variables['1']);

        $income->payment_state = 1;
        $income->save();
        $unavailable = $service->Income_GetPaymentReminderRecipients($income->id);
        $this->assertSame(0, $unavailable['status']);
    }

    public function test_whatsapp_payment_reminder_uses_unique_id_for_template_button(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-21 12:00:00'));
        $fakeTwilioClient = new IncomePaymentReminderFakeTwilioClient();
        $service = new class($fakeTwilioClient) extends incomes_controller {
            public function __construct(private IncomePaymentReminderFakeTwilioClient $fakeTwilioClient)
            {
                parent::__construct();
            }

            protected function TwilioWhatsApp_CreateClient()
            {
                return $this->fakeTwilioClient;
            }
        };
        $client = client::forceCreate([
            'name' => 'Cliente WhatsApp',
            'lastname' => 'Manual',
            'phone' => '3000000004',
            'active' => true,
        ]);
        $income = income::forceCreate([
            'unique_id' => 'INCOME-WHATSAPP-MANUAL-001',
            'client_id' => $client->id,
            'client_identification' => '9002',
            'client_name' => 'Cliente WhatsApp Manual',
            'timely_payment' => '2026-09-01',
            'cutoff_date' => '2026-09-16',
            'total' => 1000,
            'state' => 2,
            'payment_state' => 0,
        ]);

        try {
            $response = $service->Income_SendPaymentReminder($income->id, '3000000004', 'whatsapp');
        } finally {
            Carbon::setTestNow();
        }

        $expectedVariables = [
            '1' => 'Cliente WhatsApp Manual',
            '2' => 'L-001',
            '3' => '1.000',
            '4' => '2026-09-16',
            '5' => '5',
            '6' => 'INCOME-WHATSAPP-MANUAL-001',
        ];
        $this->assertSame(1, $response['status']);
        $this->assertSame($expectedVariables, whatsapp_message::first()->content_variables);
        $this->assertSame(
            $expectedVariables,
            json_decode($fakeTwilioClient->lastCreated['options']['contentVariables'], true)
        );
    }

    public function test_automatic_payment_reminder_schedule_selects_expected_days_and_channels(): void
    {
        $today = Carbon::parse('2026-09-22 07:00:00', config('app.timezone'))->startOfDay();
        Carbon::setTestNow($today);

        try {
            $client = client::forceCreate([
                'name' => 'Cliente Cadencia',
                'phone' => '3000000099',
                'active' => true,
            ]);
            $license = license::forceCreate([
                'client_id' => $client->id,
                'name' => 'Licencia Cadencia',
                'active' => true,
            ]);
            $expectedChannels = [
                0 => 'email',
                1 => 'whatsapp',
                3 => 'whatsapp',
                5 => 'sms',
                7 => 'email',
                10 => 'whatsapp',
                12 => 'sms',
                15 => 'email',
                18 => 'whatsapp',
                21 => 'sms',
                25 => 'email',
                28 => 'whatsapp',
                30 => 'email',
                33 => 'whatsapp',
                36 => 'sms',
                39 => 'whatsapp',
                42 => 'email',
                45 => 'whatsapp',
                48 => 'sms',
                52 => 'email',
                56 => 'whatsapp',
                60 => 'email',
                61 => 'whatsapp',
            ];

            foreach (array_keys($expectedChannels) as $daysOverdue) {
                $income = income::forceCreate([
                    'unique_id' => 'INCOME-CADENCE-'.$daysOverdue,
                    'client_id' => $client->id,
                    'client_identification' => '9009',
                    'client_name' => 'Cliente Cadencia',
                    'timely_payment' => $today->copy()->subDays($daysOverdue + 5)->toDateString(),
                    'cutoff_date' => $today->copy()->subDays($daysOverdue)->toDateString(),
                    'total' => 1000,
                    'state' => 2,
                    'payment_state' => 0,
                ]);
                income_license::forceCreate([
                    'income_id' => $income->id,
                    'license_id' => $license->id,
                    'license_name' => 'Licencia Cadencia',
                    'service_name' => 'Servicio',
                    'value' => 1000,
                    'total' => 1000,
                ]);
            }

            $ignoredIncome = income::forceCreate([
                'unique_id' => 'INCOME-CADENCE-2',
                'client_id' => $client->id,
                'client_identification' => '9009',
                'client_name' => 'Cliente Cadencia',
                'timely_payment' => $today->copy()->subDays(7)->toDateString(),
                'cutoff_date' => $today->copy()->subDays(2)->toDateString(),
                'total' => 1000,
                'state' => 2,
                'payment_state' => 0,
            ]);
            income_license::forceCreate([
                'income_id' => $ignoredIncome->id,
                'license_id' => $license->id,
                'license_name' => 'Licencia Cadencia',
                'service_name' => 'Servicio',
                'value' => 1000,
                'total' => 1000,
            ]);

            $service = new incomes_controller();
            $response = $service->Income_GetAllOverdueIncomes();
            $actualChannels = collect($response['data'])
                ->mapWithKeys(fn ($income) => [(int) $income->days_overdue => $income->reminder_channel])
                ->sortKeys()
                ->all();

            $this->assertSame(1, $response['status']);
            $this->assertSame($expectedChannels, $actualChannels);
            $this->assertCount(count($expectedChannels) + 1, $response['portfolio']);
            $this->assertNull(collect($response['portfolio'])->firstWhere('days_overdue', 2)->reminder_channel);
        } finally {
            Carbon::setTestNow();
        }
    }
}
