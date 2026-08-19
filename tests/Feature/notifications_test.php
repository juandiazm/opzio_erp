<?php

namespace Tests\Feature;

use App\Console\Commands\send_pay_remaining;
use App\Models\client;
use App\Models\license;
use App\Models\license_notification;
use App\Models\mail_log;
use App\Models\mail_log_attachment;
use App\Models\sms_log;
use App\traits\notifications_trait;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class notifications_test extends TestCase
{
    use notifications_trait;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.env' => 'local',
        ]);
        DB::purge('sqlite');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(1);
            $table->timestamps();
        });
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->boolean('active')->default(1);
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->string('subject');
            $table->string('view', 100);
            $table->string('from', 150);
            $table->string('as', 50)->nullable();
            $table->longText('to');
            $table->string('bcc', 150)->nullable();
            $table->longText('mail_data');
            $table->tinyInteger('attemps')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->longText('error_message')->nullable();
            $table->dateTime('send_at')->nullable();
            $table->string('notification_batch', 100)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
        Schema::create('mail_log_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mail_log_id');
            $table->string('name', 150);
            $table->string('path', 200);
            $table->timestamps();
        });
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('recipient_name', 150)->nullable();
            $table->string('to', 30);
            $table->longText('body');
            $table->tinyInteger('attempts')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('send_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('notification_batch', 100)->nullable();
            $table->unsignedBigInteger('resend_of_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Storage::fake('local');
    }

    public function test_email_creation_sanitizes_html_persists_attachments_and_supports_modes()
    {
        $client = client::create([
            'name' => 'Cliente Uno',
            'email' => 'cliente@example.test',
            'phone' => '3000000000',
            'active' => true,
        ]);
        $license = license::forceCreate(['client_id' => $client->id, 'name' => 'Licencia', 'active' => true]);
        license_notification::forceCreate([
            'license_id' => $license->id,
            'email' => 'contacto@example.test',
            'active' => true,
        ]);

        $response = $this->Notification_CreateEmail([
            'client_ids' => [$client->id],
            'recipients' => 'manual@example.test',
            'recipient_mode' => 'individual',
            'subject' => 'Aviso',
            'content' => '<p><strong>Hola</strong></p><script>alert(1)</script>',
            'from' => 'erp@example.test',
            'reply_to' => 'respuestas@example.test',
            'send_at' => '2026-08-17 12:00:00',
        ], [UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf')]);

        $this->assertSame(1, $response['status']);
        $this->assertSame(3, $response['count']);
        $this->assertSame(3, mail_log::where('view', 'mail.notification')->count());
        $this->assertSame(3, mail_log_attachment::count());
        $this->assertStringNotContainsString('<script>', mail_log::first()->mail_data['content']);
        $this->assertSame('info@opzio.co', mail_log::first()->from);
        $this->assertSame('OPZIO SAS - Información', mail_log::first()->as);
        $this->assertSame('info@opzio.co', mail_log::first()->mail_data['_reply_to']['address']);

        $massive = $this->Notification_CreateEmail([
            'recipients' => ['uno@example.test', 'dos@example.test'],
            'recipient_mode' => 'massive',
            'subject' => 'Masivo',
            'content' => '<p>Contenido</p>',
            'from' => 'erp@example.test',
        ]);

        $this->assertSame(1, $massive['status']);
        $this->assertSame(1, $massive['count']);
        $this->assertCount(2, mail_log::where('subject', 'Masivo')->first()->to);
    }

    public function test_email_queue_only_returns_null_or_due_send_dates()
    {
        $future = $this->MailLog_CreatePending('Futuro', 'mail.notification', 'erp@example.test', 'ERP', [['address' => 'future@example.test']], ['content' => '<p>futuro</p>'], null, Carbon::now()->addHour());
        $due = $this->MailLog_CreatePending('Vencido', 'mail.notification', 'erp@example.test', 'ERP', [['address' => 'due@example.test']], ['content' => '<p>vencido</p>'], null, Carbon::now()->subMinute());
        $immediate = $this->MailLog_CreatePending('Inmediato', 'mail.notification', 'erp@example.test', 'ERP', [['address' => 'now@example.test']], ['content' => '<p>ahora</p>']);

        $response = $this->MailLog_GetQueuedMails();
        $ids = collect($response['data'])->pluck('id')->all();

        $this->assertSame(1, $response['status']);
        $this->assertNotContains($future->id, $ids);
        $this->assertContains($due->id, $ids);
        $this->assertContains($immediate->id, $ids);
    }

    public function test_email_history_includes_legacy_mail_logs()
    {
        $this->MailLog_SetLog(
            'INCOME-UUID',
            'Orden de compra #123',
            'mail.purchase_order',
            'erp@example.test',
            'ERP',
            [['address' => 'cliente@example.test', 'name' => 'Cliente']],
            null,
            ['income_id' => 123],
            1,
            null
        );

        $response = $this->Notification_GetEmails();
        $legacyMail = collect($response['emails'])->firstWhere('view', 'mail.purchase_order');

        $this->assertSame(1, $response['status']);
        $this->assertNotNull($legacyMail);
        $this->assertFalse($legacyMail->can_resend);
    }

    public function test_payment_reminder_email_is_scheduled_between_eight_and_eleven()
    {
        $command = new class extends send_pay_remaining {
            public function Income_GetAllOverdueIncomes()
            {
                return [
                    'status' => 1,
                    'data' => collect([(object) [
                        'unique_id' => 'INCOME-1234567890',
                        'client' => (object) [
                            'id' => 1,
                            'name' => 'Cliente Uno',
                            'identification' => '123',
                            'active' => 1,
                        ],
                        'income_licenses' => collect([(object) [
                            'license_id' => 1,
                            'license' => (object) [
                                'service' => (object) ['name' => 'Servicio'],
                            ],
                        ]]),
                        'client_name' => 'Cliente Uno',
                        'client_identification' => '123',
                        'timely_payment' => 1,
                        'cutoff_date' => '2026-08-17',
                        'total' => 100000,
                        'payment_link' => 'https://example.test/pagar',
                        'state' => 2,
                        'days_overdue' => 1,
                        'siigo_invoice_url' => null,
                    ]]),
                ];
            }

            public function License_GetLicenseNotificationsByLicensesIds($licenseIds)
            {
                return [
                    'status' => 1,
                    'data' => [[
                        'email' => 'cliente@example.test',
                        'phone' => null,
                    ]],
                ];
            }

            public function OpenIA_MakeQuestion($message, $model = null, $options = [])
            {
                return ['status' => 1, 'data' => ['Mensaje']];
            }

            public function SendMail($MailData, $Mails, $View, $ViewData, $files, $unique_id = null, $mailer = null, $from = null, $replyTo = null)
            {
                return ['status' => 1];
            }
        };

        $this->assertSame(0, $command->handle());

        $mailLog = mail_log::where('view', 'mail.pay_remaining_grouped')->first();
        $start = Carbon::today(config('app.timezone'))->setTime(8, 0);
        $end = Carbon::today(config('app.timezone'))->setTime(11, 0);

        $this->assertNotNull($mailLog);
        $this->assertSame(0, (int) $mailLog->status);
        $this->assertNotNull($mailLog->send_at);
        $this->assertTrue($mailLog->send_at->betweenIncluded($start, $end));
    }

    public function test_sms_queue_skips_future_messages_and_processes_due_messages()
    {
        $future = sms_log::create([
            'unique_id' => 'SMS-FUTURE',
            'to' => '+573000000001',
            'body' => 'Futuro',
            'send_at' => Carbon::now()->addHour(),
        ]);
        $due = sms_log::create([
            'unique_id' => 'SMS-DUE',
            'to' => '+573000000002',
            'body' => 'Vencido',
            'send_at' => Carbon::now()->subMinute(),
        ]);
        $immediate = sms_log::create([
            'unique_id' => 'SMS-NOW',
            'to' => '+573000000003',
            'body' => 'Ahora',
        ]);

        $response = $this->Notification_ProcessSmsQueue();

        $this->assertSame(1, $response['status']);
        $this->assertSame(2, $response['data']['sent']);
        $this->assertSame(0, sms_log::find($future->id)->status);
        $this->assertSame(1, sms_log::find($due->id)->status);
        $this->assertSame(1, sms_log::find($immediate->id)->status);
    }

    public function test_direct_sms_send_creates_delivery_log()
    {
        $response = $this->TwilioSMS_SendMessage(
            '+57',
            '3000000004',
            'Mensaje directo',
            null,
            ['client_id' => 7, 'recipient_name' => 'Cliente Uno']
        );
        $sms = sms_log::where('to', '+573000000004')->first();

        $this->assertSame(1, $response['status']);
        $this->assertNotNull($sms);
        $this->assertSame(1, (int) $sms->status);
        $this->assertSame(1, (int) $sms->attempts);
        $this->assertSame(7, (int) $sms->client_id);
        $this->assertNotNull($sms->sent_at);
    }

    public function test_email_resend_replaces_recipients_and_keeps_original()
    {
        $created = $this->Notification_CreateEmail([
            'recipients' => ['original@example.test'],
            'recipient_mode' => 'massive',
            'subject' => 'Original',
            'content' => '<p>Original</p>',
            'from' => 'erp@example.test',
        ]);
        $original = mail_log::where('subject', 'Original')->first();

        $response = $this->Notification_ResendEmail($original->id, [
            'recipients' => 'nuevo@example.test',
            'subject' => 'Reenviado',
            'content' => '<p>Nuevo</p>',
            'from' => 'erp@example.test',
            'reply_to' => 'reply@example.test',
        ]);
        $resent = mail_log::where('subject', 'Reenviado')->first();

        $this->assertSame(1, $created['status']);
        $this->assertSame(1, $response['status']);
        $this->assertSame('original@example.test', $original->to[0]['address']);
        $this->assertSame('nuevo@example.test', $resent->to[0]['address']);
        $this->assertSame($original->id, $resent->mail_data['resend_of_id']);
    }

    public function test_sms_resend_replaces_phone_and_keeps_original()
    {
        $created = $this->Notification_CreateSms([
            'recipients' => '+573000000001',
            'body' => 'Original SMS',
        ]);
        $original = sms_log::where('to', '+573000000001')->first();

        $response = $this->Notification_ResendSms($original->id, [
            'recipients' => '+573000000002',
            'body' => 'Nuevo SMS',
        ]);
        $resent = sms_log::where('to', '+573000000002')->first();

        $this->assertSame(1, $created['status']);
        $this->assertSame(1, $response['status']);
        $this->assertSame('+573000000001', $original->to);
        $this->assertSame('+573000000002', $resent->to);
        $this->assertSame($original->id, $resent->resend_of_id);
    }
}