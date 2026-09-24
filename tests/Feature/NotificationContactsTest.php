<?php

namespace Tests\Feature;

use App\Models\client;
use App\Models\license;
use App\Models\license_notification;
use App\Models\notification_tag;
use App\Models\whatsapp_conversation;
use App\Models\whatsapp_message;
use App\Console\Commands\send_pay_remaining;
use App\traits\licenses_trait;
use App\traits\notification_contacts_trait;
use App\traits\whatsapp_notifications_trait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationContactsFakeMessageList
{
    public function __construct(private NotificationContactsFakeTwilioClient $client)
    {
    }

    public function create($to, array $options)
    {
        $this->client->lastCreated = ['to' => $to, 'options' => $options];
        return (object) [
            'sid' => 'SM-WHATSAPP-CONTACT-001',
            'status' => 'queued',
            'errorCode' => null,
            'errorMessage' => null,
            'dateSent' => null,
        ];
    }
}

class NotificationContactsFakeTwilioClient
{
    public array $lastCreated = [];

    public function __construct()
    {
        $this->messages = new NotificationContactsFakeMessageList($this);
    }

    public function __get($name)
    {
        if ($name === 'messages') {
            return $this->messages;
        }

        throw new \RuntimeException('Unknown fake Twilio property: '.$name);
    }
}

class NotificationContactsTest extends TestCase
{
    use licenses_trait;
    use notification_contacts_trait;
    use whatsapp_notifications_trait;

    private NotificationContactsFakeTwilioClient $twilioClient;

    protected function TwilioWhatsApp_CreateClient()
    {
        return $this->twilioClient;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.env' => 'testing',
            'services.twilio.sid' => 'AC00000000000000000000000000000000',
            'services.twilio.token' => 'test-token',
            'services.twilio.whatsapp.from' => 'whatsapp:+573145433746',
            'services.twilio.whatsapp.messaging_service_sid' => null,
            'services.twilio.whatsapp.status_callback_url' => null,
        ]);
        DB::purge('sqlite');
        $this->twilioClient = new NotificationContactsFakeTwilioClient();

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
        Schema::create('license_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('license_id')->nullable();
            $table->string('name', 150)->nullable();
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
        });
    }

    public function test_contacts_accept_multiple_channels_and_tags_with_license_priority(): void
    {
        $client = client::create([
            'name' => 'Cliente Etiquetado',
            'email' => 'cliente@example.test',
            'phone' => '3000000001',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia Cobranza',
            'active' => true,
        ]);
        $tag = notification_tag::create(['name' => 'Cobranza', 'slug' => 'cobranza']);

        $clientResponse = $this->NotificationContact_AddClient($client->id, [
            'name' => 'Contacto Cliente',
            'phone' => '3000000002',
            'channels' => ['sms', 'whatsapp'],
            'tag_ids' => [$tag->id],
        ]);
        $licenseResponse = $this->NotificationContact_AddLicense($license->id, [
            'name' => 'Contacto Licencia',
            'phone' => '3000000003',
            'channels' => ['whatsapp'],
            'tag_ids' => [$tag->id],
        ]);

        $this->assertSame(1, $clientResponse['status']);
        $this->assertSame(1, $licenseResponse['status']);
        $this->assertSame(['sms', 'whatsapp'], $clientResponse['contact']['channels']);
        $this->assertSame(['Cobranza'], collect($clientResponse['contact']['tags'])->pluck('name')->all());

        $invalidEmail = $this->NotificationContact_AddClient($client->id, [
            'name' => 'Correo Invalido',
            'type' => 'email',
            'value' => 'invalido@example.test',
            'channels' => ['sms'],
        ]);
        $invalidUpdate = $this->NotificationContact_UpdateClient($clientResponse['contact']['id'], [
            'name' => 'Contacto Cliente',
            'type' => 'phone',
            'value' => '3000000002',
            'channels' => ['email'],
        ]);
        $this->assertSame(0, $invalidEmail['status']);
        $this->assertSame(0, $invalidUpdate['status']);

        $priority = $this->License_GetTaggedLicenseNotificationsByLicensesIds([$license->id], $client->id, 'cobranza');
        $this->assertSame('3000000003', $priority['data'][0]['phone']);
        $this->assertSame($license->id, $priority['data'][0]['license_id']);

        license_notification::where('license_id', $license->id)->delete();
        $fallback = $this->License_GetTaggedLicenseNotificationsByLicensesIds([$license->id], $client->id, 'cobranza');
        $this->assertSame('3000000002', $fallback['data'][0]['phone']);
        $this->assertNull($fallback['data'][0]['license_id']);
    }

    public function test_directory_contact_creation_uses_selected_owner(): void
    {
        $client = client::create([
            'name' => 'Cliente Directorio',
            'active' => true,
        ]);
        $license = license::forceCreate([
            'client_id' => $client->id,
            'name' => 'Licencia Directorio',
            'active' => true,
        ]);

        $response = $this->NotificationContact_AddDirectory([
            'name' => 'Contacto Manual',
            'type' => 'phone',
            'value' => '3000000010',
            'channels' => ['whatsapp'],
            'client_id' => $client->id,
            'license_id' => $license->id,
        ]);
        $missingOwner = $this->NotificationContact_AddDirectory([
            'name' => 'Sin propietario',
            'type' => 'phone',
            'value' => '3000000011',
            'channels' => ['sms'],
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame($license->id, $response['contact']['license_id']);
        $this->assertSame($client->id, $response['contact']['client_id']);
        $this->assertSame(0, $missingOwner['status']);
    }

    public function test_directory_message_context_returns_active_contact_data(): void
    {
        $client = client::create([
            'name' => 'Cliente Mensajes',
            'lastname' => 'Directos',
            'active' => true,
        ]);
        $contact = license_notification::create([
            'client_id' => $client->id,
            'name' => 'Contacto WhatsApp',
            'phone' => '3000000012',
            'channels' => ['sms', 'whatsapp'],
            'active' => true,
        ]);

        $response = $this->NotificationContact_GetMessageContext($contact->id);

        $this->assertSame(1, $response['status']);
        $this->assertSame($contact->id, $response['contact']['id']);
        $this->assertSame('3000000012', $response['contact']['phone']);
        $this->assertSame(['sms', 'whatsapp'], $response['contact']['channels']);
    }

    public function test_sms_contacts_receive_whatsapp_channel_in_migration(): void
    {
        $smsContactId = DB::table('license_notifications')->insertGetId([
            'name' => 'Solo SMS',
            'phone' => '3000000013',
            'channels' => json_encode(['sms']),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bothChannelsId = DB::table('license_notifications')->insertGetId([
            'name' => 'SMS y WhatsApp',
            'phone' => '3000000014',
            'channels' => json_encode(['sms', 'whatsapp']),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailContactId = DB::table('license_notifications')->insertGetId([
            'name' => 'Solo correo',
            'email' => 'correo@example.test',
            'channels' => json_encode(['email']),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_22_000004_add_whatsapp_to_sms_contact_channels.php');
        $migration->up();

        $this->assertSame(['sms', 'whatsapp'], json_decode((string) DB::table('license_notifications')->where('id', $smsContactId)->value('channels'), true));
        $this->assertSame(['sms', 'whatsapp'], json_decode((string) DB::table('license_notifications')->where('id', $bothChannelsId)->value('channels'), true));
        $this->assertSame(['email'], json_decode((string) DB::table('license_notifications')->where('id', $emailContactId)->value('channels'), true));

        $migration->up();
        $this->assertSame(['sms', 'whatsapp'], json_decode((string) DB::table('license_notifications')->where('id', $smsContactId)->value('channels'), true));
    }

    public function test_whatsapp_template_is_queued_and_processed_with_content_sid(): void
    {
        $response = $this->Notification_QueueWhatsappTemplate(
            '3000000004',
            7,
            'Contacto WhatsApp',
            'HX9990ce79b043c2a8a8fc31aa3b220a46',
            ['1' => 'Cliente', '2' => '100.000', '3' => 'https://example.test/pagar']
        );

        $this->assertSame(1, $response['status']);
        $this->assertSame('pending', whatsapp_message::first()->status);

        $processed = $this->Notification_ProcessWhatsappQueue();
        $message = whatsapp_message::first();

        $this->assertSame(1, $processed['data']['sent']);
        $this->assertSame('HX9990ce79b043c2a8a8fc31aa3b220a46', $this->twilioClient->lastCreated['options']['contentSid']);
        $this->assertSame('queued', $message->status);
        $this->assertSame(1, (int) $message->attempts);
        $this->assertSame('whatsapp:+573000000004', $this->twilioClient->lastCreated['to']);
    }

    public function test_whatsapp_media_is_saved_locally_and_exposes_a_local_view_url(): void
    {
        Storage::fake('erp_media');
        Http::fake([
            'https://api.twilio.com/*' => Http::response('image-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $this->Notification_HandleWhatsappIncoming([
            'MessageSid' => 'SM-INBOUND-WHATSAPP-MEDIA-001',
            'From' => 'whatsapp:+573000000006',
            'To' => 'whatsapp:+573145433746',
            'NumMedia' => '1',
            'MediaUrl0' => 'https://api.twilio.com/2010-04-01/Accounts/AC000/Media/MG000',
            'MediaContentType0' => 'image/jpeg',
        ]);

        $message = whatsapp_message::firstOrFail();
        $path = 'whatsapp/media/'.$message->id.'/0.jpg';

        Storage::disk('erp_media')->assertExists($path);
        $this->assertSame($path, $message->media[0]['storage_path']);

        $payload = $this->Notification_GetWhatsappConversation($message->conversation_id);
        $this->assertStringContainsString(
            '/admin/notifications/whatsapp/media/'.$message->id.'/0',
            $payload['messages'][0]['media'][0]['view_url']
        );
    }

    public function test_whatsapp_media_migration_backfills_existing_media_idempotently(): void
    {
        Storage::fake('erp_media');
        Http::fake([
            'https://api.twilio.com/*' => Http::response('%PDF-1.4 local', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $conversation = whatsapp_conversation::create([
            'unique_id' => 'WA-MEDIA-MIGRATION-001',
            'phone' => '+573000000007',
            'business_address' => 'whatsapp:+573145433746',
        ]);
        whatsapp_message::create([
            'unique_id' => 'WA-MEDIA-MESSAGE-001',
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'from' => 'whatsapp:+573000000007',
            'to' => 'whatsapp:+573145433746',
            'media' => [[
                'url' => 'https://api.twilio.com/2010-04-01/Accounts/AC000/Media/MG001',
                'content_type' => 'application/pdf',
            ]],
        ]);

        $migration = require database_path('migrations/2026_09_24_000001_migrate_whatsapp_media_to_storage.php');
        $migration->up();
        $migration->up();

        $message = whatsapp_message::firstOrFail();
        Storage::disk('erp_media')->assertExists('whatsapp/media/'.$message->id.'/0.pdf');
        $this->assertSame('whatsapp/media/'.$message->id.'/0.pdf', $message->media[0]['storage_path']);
        Http::assertSentCount(1);
    }

    public function test_payment_reminder_command_queues_tagged_whatsapp_contact(): void
    {
        $client = client::create([
            'name' => 'Cliente Cobranza',
            'email' => 'cobranza@example.test',
            'phone' => '3000000005',
            'active' => true,
        ]);
        $twilioClient = $this->twilioClient;
        $command = new class($twilioClient, $client->id) extends send_pay_remaining {
            public function __construct(private NotificationContactsFakeTwilioClient $fakeClient, private int $clientId)
            {
                parent::__construct();
            }

            protected function TwilioWhatsApp_CreateClient()
            {
                return $this->fakeClient;
            }

            public function Income_GetAllOverdueIncomes()
            {
                return ['status' => 1, 'data' => collect([(object) [
                    'unique_id' => 'INCOME-WHATSAPP-001',
                    'client' => (object) [
                        'id' => $this->clientId,
                        'name' => 'Cliente Cobranza',
                        'identification' => '9001',
                        'active' => 1,
                    ],
                    'income_licenses' => collect([(object) [
                        'license_id' => 1,
                        'license' => (object) ['service' => (object) ['name' => 'Servicio']],
                    ]]),
                    'client_name' => 'Cliente Cobranza',
                    'client_identification' => '9001',
                    'timely_payment' => '2026-09-01',
                    'cutoff_date' => '2026-09-10',
                    'total' => 125000,
                    'payment_link' => 'https://example.test/pagar',
                    'state' => 2,
                    'days_overdue' => 11,
                    'siigo_invoice_url' => null,
                ]])];
            }

            public function License_GetLicenseNotificationsByLicensesIds($licenseIds)
            {
                return ['status' => 1, 'data' => [[
                    'name' => 'Contacto WhatsApp',
                    'email' => null,
                    'phone' => '3000000005',
                    'channels' => ['whatsapp'],
                    'active' => true,
                    'tags' => [['slug' => 'cobranza']],
                ]]];
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
        $message = whatsapp_message::first();
        $this->assertNotNull($message);
        $this->assertSame('pending', $message->status);
        $this->assertSame('HX9990ce79b043c2a8a8fc31aa3b220a46', $message->content_sid);
        $this->assertSame('whatsapp:+573000000005', $message->to);
        $this->assertNotNull($message->send_at);
        $this->assertSame([
            '1' => 'Contacto WhatsApp',
            '2' => 'P-001',
            '3' => '125.000',
            '4' => '2026-09-10',
            '5' => '11',
            '6' => 'INCOME-WHATSAPP-001',
        ], $message->content_variables);
    }
}
