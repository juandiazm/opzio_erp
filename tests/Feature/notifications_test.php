<?php

namespace Tests\Feature;

use App\Events\pusherEvents;
use App\Console\Commands\send_queued_mails;
use App\Console\Commands\send_pay_remaining;
use App\Mail\CustomMail;
use App\Models\client;
use App\Models\license;
use App\Models\license_notification;
use App\Models\mail_log;
use App\Models\mail_log_attachment;
use App\Models\sms_log;
use App\Models\whatsapp_conversation;
use App\Models\whatsapp_message;
use App\traits\notifications_trait;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

class NotificationTwilioFakeMessageList
{
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function create($to, array $options)
    {
        $this->client->lastCreated = ['to' => $to, 'options' => $options];
        return $this->client->createMessage;
    }
}

class NotificationTwilioFakeMessageContext
{
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function fetch()
    {
        return $this->client->fetchMessage;
    }
}

class NotificationTwilioFakeClient
{
    public $createMessage;
    public $fetchMessage;
    public $lastCreated;
    public $lastFetchedSid;

    private $messageList;

    public function __construct()
    {
        $this->messageList = new NotificationTwilioFakeMessageList($this);
    }

    public function __get($name)
    {
        if ($name === 'messages') {
            return $this->messageList;
        }

        throw new \RuntimeException('Unknown fake Twilio property: '.$name);
    }

    public function __call($name, $arguments)
    {
        if ($name === 'messages') {
            $this->lastFetchedSid = $arguments[0] ?? null;
            return new NotificationTwilioFakeMessageContext($this);
        }

        throw new \RuntimeException('Unknown fake Twilio method: '.$name);
    }
}

class notifications_test extends TestCase
{
    use notifications_trait;

    protected $twilioClient;

    protected function TwilioSMS_CreateClient()
    {
        return $this->twilioClient;
    }

    protected function TwilioWhatsApp_CreateClient()
    {
        return $this->twilioClient;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->twilioClient = new NotificationTwilioFakeClient();
        $this->twilioClient->createMessage = (object) [
            'sid' => 'SM00000000000000000000000000000001',
            'status' => 'queued',
            'errorCode' => null,
            'errorMessage' => null,
            'dateSent' => null,
        ];
        $this->twilioClient->fetchMessage = $this->twilioClient->createMessage;

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.env' => 'local',
            'services.twilio.sid' => 'AC00000000000000000000000000000000',
            'services.twilio.token' => 'test-token',
            'services.twilio.whatsapp.from' => 'whatsapp:+573145433746',
            'services.twilio.whatsapp.messaging_service_sid' => null,
            'services.twilio.whatsapp.webhook_url' => null,
            'services.twilio.whatsapp.status_callback_url' => null,
            'services.twilio.whatsapp.validate_webhooks' => true,
        ]);
        $this->app['env'] = 'local';
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
            $table->string('twilio_sid', 64)->nullable();
            $table->string('twilio_status', 50)->nullable();
            $table->unsignedInteger('twilio_error_code')->nullable();
            $table->text('twilio_error_message')->nullable();
            $table->dateTime('twilio_checked_at')->nullable();
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
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('phone', 30);
            $table->string('business_address', 80);
            $table->string('wa_id', 40)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('profile_name', 150)->nullable();
            $table->unsignedInteger('unread_count')->default(0)->index();
            $table->text('last_message_preview')->nullable();
            $table->dateTime('last_message_at')->nullable()->index();
            $table->dateTime('last_inbound_at')->nullable();
            $table->dateTime('window_expires_at')->nullable();
            $table->dateTime('last_read_at')->nullable();
            $table->string('last_inbound_sid', 64)->nullable();
            $table->string('last_outbound_sid', 64)->nullable();
            $table->timestamps();
            $table->unique(['phone', 'business_address']);
        });
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 100)->unique();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('twilio_sid', 64)->nullable()->unique();
            $table->string('direction', 20)->index();
            $table->string('from', 80);
            $table->string('to', 80);
            $table->longText('body')->nullable();
            $table->string('message_type', 40)->default('text');
            $table->json('media')->nullable();
            $table->string('content_sid', 64)->nullable()->index();
            $table->json('content_variables')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->unsignedInteger('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('send_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('status_updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
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
        $failed = $this->MailLog_CreatePending('Fallido', 'mail.notification', 'erp@example.test', 'ERP', [['address' => 'failed@example.test']], ['content' => '<p>fallido</p>']);
        $failed->status = 2;
        $failed->save();

        $response = $this->MailLog_GetQueuedMails();
        $ids = collect($response['data'])->pluck('id')->all();

        $this->assertSame(1, $response['status']);
        $this->assertNotContains($future->id, $ids);
        $this->assertContains($due->id, $ids);
        $this->assertContains($immediate->id, $ids);
        $this->assertNotContains($failed->id, $ids);
    }

    public function test_email_status_can_be_marked_failed_and_requeued()
    {
        $mail = $this->MailLog_CreatePending('Estado', 'mail.notification', 'erp@example.test', 'ERP', [['address' => 'estado@example.test']], ['content' => '<p>estado</p>']);
        $mail->attemps = 2;
        $mail->save();

        $failed = $this->Notification_ChangeEmailStatus($mail->id, 2);

        $this->assertSame(1, $failed['status']);
        $mail->refresh();
        $this->assertSame(2, (int) $mail->status);
        $this->assertSame('Marcado como fallido manualmente', $mail->error_message);
        $this->assertNotContains($mail->id, collect($this->MailLog_GetQueuedMails()['data'])->pluck('id')->all());

        $queued = $this->Notification_ChangeEmailStatus($mail->id, 0);

        $this->assertSame(1, $queued['status']);
        $mail->refresh();
        $this->assertSame(0, (int) $mail->status);
        $this->assertSame(0, (int) $mail->attemps);
        $this->assertNull($mail->error_message);
        $this->assertTrue($mail->send_at->lessThanOrEqualTo(Carbon::now()));
        $this->assertContains($mail->id, collect($this->MailLog_GetQueuedMails()['data'])->pluck('id')->all());
    }

    public function test_sent_email_status_cannot_be_changed_manually()
    {
        $mail = $this->MailLog_CreatePending('Enviado', 'mail.notification', 'erp@example.test', 'ERP', [['address' => 'enviado@example.test']], ['content' => '<p>enviado</p>']);
        $mail->status = 1;
        $mail->sent_at = Carbon::now();
        $mail->save();

        $response = $this->Notification_ChangeEmailStatus($mail->id, 0);

        $this->assertSame(0, $response['status']);
        $this->assertSame(1, (int) $mail->fresh()->status);
    }

    public function test_queued_mail_command_returns_success_when_queue_is_empty()
    {
        $command = new send_queued_mails();

        $this->assertSame(0, $command->handle());
    }

    public function test_queued_mail_command_defers_external_automated_mail_on_sunday_but_sends_internal_mail()
    {
        config(['app.env' => 'testing']);
        Carbon::setTestNow(Carbon::parse('2026-08-23 10:00:00', config('app.timezone')));
        Mail::fake();

        $external = mail_log::create([
            'unique_id' => 'EXTERNAL-SUNDAY',
            'subject' => 'Cliente',
            'view' => 'mail.notification',
            'from' => 'info@opzio.co',
            'as' => 'Opzio',
            'to' => [['address' => 'cliente@example.test', 'name' => 'Cliente']],
            'mail_data' => [
                '_defer_external_on_sunday' => true,
                '_from' => ['address' => 'info@opzio.co', 'name' => 'Opzio'],
                '_reply_to' => ['address' => 'info@opzio.co', 'name' => 'Opzio'],
            ],
            'status' => 0,
        ]);
        $internal = mail_log::create([
            'unique_id' => 'INTERNAL-SUNDAY',
            'subject' => 'Interno',
            'view' => 'mail.notification',
            'from' => 'info@opzio.co',
            'as' => 'Opzio',
            'to' => [['address' => 'equipo@opzio.co', 'name' => 'Equipo']],
            'mail_data' => [
                '_defer_external_on_sunday' => true,
                '_from' => ['address' => 'info@opzio.co', 'name' => 'Opzio'],
                '_reply_to' => ['address' => 'info@opzio.co', 'name' => 'Opzio'],
            ],
            'status' => 0,
        ]);

        try {
            $this->assertSame(0, (new send_queued_mails())->handle());
        } finally {
            Carbon::setTestNow();
            config(['app.env' => 'local']);
        }

        Mail::assertQueued(CustomMail::class, 1);
        $this->assertSame(0, (int) $external->fresh()->status);
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

    public function test_notification_list_exposes_dates_in_bogota_time()
    {
        $mail = mail_log::create([
            'unique_id' => 'BOGOTA-DATE-EMAIL',
            'subject' => 'Fecha Bogotá',
            'view' => 'mail.notification',
            'from' => 'info@opzio.co',
            'as' => 'OPZIO SAS - Información',
            'to' => [['address' => 'cliente@example.test']],
            'mail_data' => ['content' => '<p>Contenido</p>'],
            'status' => 1,
            'send_at' => '2026-08-19 09:30:00',
            'sent_at' => '2026-08-19 10:00:00',
        ]);
        $sms_log = sms_log::create([
            'unique_id' => 'BOGOTA-DATE-SMS',
            'recipient_name' => 'Cliente',
            'to' => '+573000000000',
            'body' => 'Mensaje',
            'status' => 1,
            'send_at' => '2026-08-19 11:15:00',
            'sent_at' => '2026-08-19 11:20:00',
        ]);

        $emailResponse = $this->Notification_GetEmails();
        $smsResponse = $this->Notification_GetSms();
        $email = collect($emailResponse['emails'])->firstWhere('id', $mail->id);
        $sms = collect($smsResponse['sms'])->firstWhere('id', $sms_log->id);

        $this->assertSame('2026-08-19T09:30', $email->send_at_local);
        $this->assertSame('2026-08-19T10:00', $email->sent_at_local);
        $this->assertSame('2026-08-19T11:15', $sms->send_at_local);
        $this->assertSame('2026-08-19T11:20', $sms->sent_at_local);
    }

    public function test_notification_histories_filter_by_inclusive_created_date_range()
    {
        $oldMail = mail_log::create([
            'unique_id' => 'FILTER-OLD-EMAIL',
            'subject' => 'Correo anterior',
            'view' => 'mail.notification',
            'from' => 'info@opzio.co',
            'as' => 'OPZIO SAS - Información',
            'to' => [['address' => 'old@example.test']],
            'mail_data' => ['content' => '<p>Anterior</p>'],
            'status' => 1,
            'created_at' => '2026-08-18 23:59:59',
            'updated_at' => '2026-08-18 23:59:59',
        ]);
        $todayMail = mail_log::create([
            'unique_id' => 'FILTER-TODAY-EMAIL',
            'subject' => 'Correo de hoy',
            'view' => 'mail.notification',
            'from' => 'info@opzio.co',
            'as' => 'OPZIO SAS - Información',
            'to' => [['address' => 'today@example.test']],
            'mail_data' => ['content' => '<p>Hoy</p>'],
            'status' => 1,
            'created_at' => '2026-08-19 12:00:00',
            'updated_at' => '2026-08-19 12:00:00',
        ]);
        $oldSms = sms_log::create([
            'unique_id' => 'FILTER-OLD-SMS',
            'to' => '+573000000001',
            'body' => 'SMS anterior',
            'status' => 1,
            'created_at' => '2026-08-18 23:59:59',
            'updated_at' => '2026-08-18 23:59:59',
        ]);
        $todaySms = sms_log::create([
            'unique_id' => 'FILTER-TODAY-SMS',
            'to' => '+573000000002',
            'body' => 'SMS de hoy',
            'status' => 1,
            'created_at' => '2026-08-19 12:00:00',
            'updated_at' => '2026-08-19 12:00:00',
        ]);

        $emailResponse = $this->Notification_GetEmails(['size' => 100], null, null, '2026-08-19', '2026-08-19');
        $smsResponse = $this->Notification_GetSms(['size' => 100], null, null, '2026-08-19', '2026-08-19');

        $emailIds = collect($emailResponse['emails'])->pluck('id')->all();
        $smsIds = collect($smsResponse['sms'])->pluck('id')->all();
        $this->assertContains($todayMail->id, $emailIds);
        $this->assertNotContains($oldMail->id, $emailIds);
        $this->assertContains($todaySms->id, $smsIds);
        $this->assertNotContains($oldSms->id, $smsIds);
    }

    public function test_legacy_email_detail_renders_body_and_supports_resend()
    {
        $this->MailLog_SetLog(
            'PAYMENT-REPORT-UUID',
            'Reporte de pagos',
            'mail.pay_remaining_report',
            'erp@example.test',
            'ERP',
            [['address' => 'cliente@example.test', 'name' => 'Cliente']],
            null,
            [
                'report_message' => [[
                    'order_id' => 'ORDER-123',
                    'client' => 'Cliente Uno',
                    'identification' => '123',
                    'total' => 100000,
                ]],
            ],
            1,
            []
        );
        $original = mail_log::where('subject', 'Reporte de pagos')->first();

        $detail = $this->Notification_GetEmail($original->id);
        $resend = $this->Notification_ResendEmail($original->id, [
            'recipients' => 'nuevo@example.test',
            'subject' => 'Reporte reenviado',
            'from' => 'erp@example.test',
        ]);

        $this->assertSame(1, $detail['status']);
        $this->assertStringContainsString('Reporte de Recordatorios de Pago', $detail['email']['content']);
        $this->assertTrue($detail['email']['can_resend']);
        $this->assertSame(1, $resend['status']);
        $this->assertSame('nuevo@example.test', mail_log::where('subject', 'Reporte reenviado')->first()->to[0]['address']);
    }

    public function test_email_detail_returns_body_status_and_resend_contract()
    {
        $this->Notification_CreateEmail([
            'recipients' => 'cliente@example.test',
            'recipient_mode' => 'massive',
            'subject' => 'Detalle',
            'content' => '<p>Contenido visible</p>',
            'from' => 'erp@example.test',
        ]);
        $mail = mail_log::where('subject', 'Detalle')->first();

        $response = $this->Notification_GetEmail($mail->id);
        $email = $response['email'];

        $this->assertSame(1, $response['status']);
        $this->assertSame('<p>Contenido visible</p>', $email['content']);
        $this->assertSame('cliente@example.test', $email['recipients'][0]['address']);
        $this->assertSame('Pendiente', $email['status_string']);
        $this->assertTrue($email['can_resend']);
    }

    public function test_payment_reminder_email_and_sms_share_random_schedule_between_eight_and_eleven()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 07:00:00', config('app.timezone')));
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
                        'phone' => '3000000001',
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

        $smsLog = sms_log::where('to', '+573000000001')->first();

        $this->assertNotNull($smsLog);
        $this->assertSame(0, (int) $smsLog->status);
        $this->assertTrue($smsLog->send_at->equalTo($mailLog->send_at));

        $clientMailCount = mail_log::where('view', 'mail.pay_remaining_grouped')->count();
        Carbon::setTestNow(Carbon::parse('2026-08-23 07:00:00', config('app.timezone')));
        $this->assertSame(0, $command->handle());
        $this->assertSame($clientMailCount, mail_log::where('view', 'mail.pay_remaining_grouped')->count());
        Carbon::setTestNow();
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
        $sms = sms_log::where('to', '+573145433746')->first();

        $this->assertSame(1, $response['status']);
        $this->assertNotNull($sms);
        $this->assertSame(1, (int) $sms->status);
        $this->assertSame(1, (int) $sms->attempts);
        $this->assertSame(7, (int) $sms->client_id);
        $this->assertNotNull($sms->sent_at);
        $this->assertSame('SM00000000000000000000000000000001', $sms->twilio_sid);
        $this->assertSame('queued', $sms->twilio_status);
    }

    public function test_sms_delivery_validation_marks_delivered_message()
    {
        $sms = sms_log::create([
            'unique_id' => 'SMS-DELIVERED',
            'to' => '+573000000005',
            'body' => 'Mensaje entregado',
            'status' => 1,
            'twilio_sid' => 'SM00000000000000000000000000000002',
        ]);
        $this->twilioClient->fetchMessage = (object) [
            'sid' => $sms->twilio_sid,
            'status' => 'delivered',
            'errorCode' => null,
            'errorMessage' => null,
            'dateSent' => Carbon::now(),
        ];

        $response = $this->Notification_ValidateSmsDelivery($sms->id);
        $sms->refresh();

        $this->assertSame(1, $response['status']);
        $this->assertSame('delivered', $sms->twilio_status);
        $this->assertSame(1, (int) $sms->status);
        $this->assertNotNull($sms->twilio_checked_at);
        $this->assertSame($sms->twilio_sid, $this->twilioClient->lastFetchedSid);
    }

    public function test_sms_delivery_validation_marks_undelivered_message_as_failed()
    {
        $sms = sms_log::create([
            'unique_id' => 'SMS-UNDELIVERED',
            'to' => '+573000000006',
            'body' => 'Mensaje no entregado',
            'status' => 1,
            'twilio_sid' => 'SM00000000000000000000000000000003',
        ]);
        $this->twilioClient->fetchMessage = (object) [
            'sid' => $sms->twilio_sid,
            'status' => 'undelivered',
            'errorCode' => 30007,
            'errorMessage' => 'Message Delivery - Unknown destination handset',
            'dateSent' => null,
        ];

        $response = $this->Notification_ValidateSmsDelivery($sms->id);
        $sms->refresh();

        $this->assertSame(1, $response['status']);
        $this->assertSame('undelivered', $sms->twilio_status);
        $this->assertSame(2, (int) $sms->status);
        $this->assertSame(30007, (int) $sms->twilio_error_code);
        $this->assertSame('Message Delivery - Unknown destination handset', $sms->error_message);
    }

    public function test_sms_delivery_validation_rejects_legacy_log_without_twilio_sid()
    {
        $sms = sms_log::create([
            'unique_id' => 'SMS-WITHOUT-SID',
            'to' => '+573000000007',
            'body' => 'Mensaje antiguo',
            'status' => 1,
        ]);

        $response = $this->Notification_ValidateSmsDelivery($sms->id);

        $this->assertSame(0, $response['status']);
        $this->assertStringContainsString('no tiene un SID', $response['message']);
        $this->assertNull($this->twilioClient->lastFetchedSid);
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

    public function test_whatsapp_incoming_webhook_is_idempotent_and_updates_unread_window()
    {
        $payload = [
            'MessageSid' => 'SM-INBOUND-WHATSAPP-001',
            'From' => 'whatsapp:+573000000010',
            'To' => 'whatsapp:+573145433746',
            'Body' => 'Hola desde WhatsApp',
            'ProfileName' => 'Cliente WhatsApp',
            'WaId' => '573000000010',
            'NumMedia' => '0',
        ];

        $response = $this->Notification_HandleWhatsappIncoming($payload);
        $duplicate = $this->Notification_HandleWhatsappIncoming($payload);
        $conversation = whatsapp_conversation::first();

        $this->assertSame(1, $response['status']);
        $this->assertFalse($response['duplicate'] ?? false);
        $this->assertTrue($duplicate['duplicate']);
        $this->assertSame('+573000000010', $conversation->phone);
        $this->assertSame(1, (int) $conversation->unread_count);
        $this->assertNotNull($conversation->window_expires_at);
        $this->assertSame(1, whatsapp_message::count());
        $this->assertSame('received', whatsapp_message::first()->status);

        $read = $this->Notification_MarkWhatsappConversationRead($conversation->id);
        $this->assertSame(1, $read['status']);
        $this->assertSame(0, (int) $conversation->fresh()->unread_count);
        $this->assertSame(0, $this->Notification_GetWhatsappUnreadCount()['unread_count']);
    }

    public function test_whatsapp_incoming_broadcasts_realtime_message_event()
    {
        Event::fake([pusherEvents::class]);

        $response = $this->Notification_HandleWhatsappIncoming([
            'MessageSid' => 'SM-INBOUND-WHATSAPP-PUSHER-001',
            'From' => 'whatsapp:+573000000014',
            'To' => 'whatsapp:+573145433746',
            'Body' => 'Actualiza el chat',
        ]);

        Event::assertDispatched(pusherEvents::class, function (pusherEvents $event) use ($response) {
            return $event->SERVICE_CHANNEL === 'opzio-channel-whatsapp'
                && $event->SERVICE_EVENT === 'opzio-event-message'
                && ($event->message['conversation_id'] ?? null) === $response['conversation_id']
                && ($event->message['message_id'] ?? null) === $response['message_id'];
        });
    }

    public function test_whatsapp_webhooks_are_not_protected_by_web_integration_token()
    {
        $incoming = app('router')->getRoutes()->match(Request::create('/api/webhooks/twilio/whatsapp/incoming', 'POST'));
        $status = app('router')->getRoutes()->match(Request::create('/api/webhooks/twilio/whatsapp/status', 'POST'));

        $this->assertNotContains('web_api_token', $incoming->gatherMiddleware());
        $this->assertNotContains('web_api_token', $status->gatherMiddleware());
    }

    public function test_whatsapp_send_allows_freeform_inside_window_and_records_provider_sid()
    {
        $this->Notification_HandleWhatsappIncoming([
            'MessageSid' => 'SM-INBOUND-WHATSAPP-002',
            'From' => 'whatsapp:+573000000011',
            'To' => 'whatsapp:+573145433746',
            'Body' => 'Necesito ayuda',
        ]);
        $conversation = whatsapp_conversation::first();

        $response = $this->Notification_SendWhatsappMessage($conversation->id, ['body' => 'Claro, te ayudamos.']);
        $message = whatsapp_message::where('direction', 'outbound')->first();

        $this->assertSame(1, $response['status']);
        $this->assertSame('whatsapp:+573000000011', $this->twilioClient->lastCreated['to']);
        $this->assertSame('Claro, te ayudamos.', $this->twilioClient->lastCreated['options']['body']);
        $this->assertSame('queued', $message->status);
        $this->assertSame('SM00000000000000000000000000000001', $message->twilio_sid);
        $this->assertSame(1, (int) $message->attempts);
    }

    public function test_whatsapp_requires_template_outside_window_and_sends_content_sid()
    {
        $conversation = whatsapp_conversation::create([
            'unique_id' => 'WA-CLOSED-001',
            'phone' => '+573000000012',
            'business_address' => 'whatsapp:+573145433746',
            'display_name' => 'Ventana cerrada',
            'window_expires_at' => Carbon::now()->subMinute(),
        ]);

        $freeform = $this->Notification_SendWhatsappMessage($conversation->id, ['body' => 'No debe salir']);
        $this->assertSame(0, $freeform['status']);
        $this->assertSame(0, whatsapp_message::count());

        $contentSid = 'HX'.str_repeat('a', 32);
        $templated = $this->Notification_SendWhatsappMessage($conversation->id, [
            'content_sid' => $contentSid,
            'content_variables' => ['1' => 'Cliente'],
        ]);
        $message = whatsapp_message::first();

        $this->assertSame(1, $templated['status']);
        $this->assertSame($contentSid, $this->twilioClient->lastCreated['options']['contentSid']);
        $this->assertSame('{"1":"Cliente"}', $this->twilioClient->lastCreated['options']['contentVariables']);
        $this->assertSame($contentSid, $message->content_sid);
    }

    public function test_whatsapp_webhook_signature_accepts_twilio_signature_and_rejects_forged_request()
    {
        $url = 'https://erp.example.test/api/webhooks/twilio/whatsapp/incoming';
        $parameters = [
            'MessageSid' => 'SM-SIGNED-WHATSAPP-001',
            'From' => 'whatsapp:+573000000013',
            'To' => 'whatsapp:+573145433746',
            'Body' => 'Mensaje firmado',
        ];
        config(['services.twilio.whatsapp.webhook_url' => $url]);

        $request = Request::create($url, 'POST', $parameters);
        $validator = new RequestValidator('test-token');
        $request->headers->set('X-Twilio-Signature', $validator->computeSignature($url, $parameters));

        $this->assertTrue($this->TwilioWhatsApp_ValidateWebhook($request, 'incoming'));

        $request->headers->set('X-Twilio-Signature', 'invalid-signature');
        $this->assertFalse($this->TwilioWhatsApp_ValidateWebhook($request, 'incoming'));
    }
}