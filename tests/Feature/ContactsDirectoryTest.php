<?php

namespace Tests\Feature;

use App\Exportable\contacts_directory;
use App\Http\Controllers\notifications_controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactsDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('name')->nullable();
            $table->string('unique_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('license_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('license_id')->nullable();
            $table->string('name')->nullable();
            $table->string('value')->nullable();
            $table->string('type')->nullable();
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
    }

    public function test_directory_filters_contacts_by_owner_type_channel_and_tag(): void
    {
        $firstClient = DB::table('clients')->insertGetId([
            'name' => 'Cliente Directorio',
            'lastname' => 'Uno',
            'photo' => 'cliente-directorio.png',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondClient = DB::table('clients')->insertGetId([
            'name' => 'Otro Cliente',
            'lastname' => 'Dos',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $licenseId = DB::table('licenses')->insertGetId([
            'client_id' => $firstClient,
            'name' => 'Licencia Directorio',
            'unique_id' => 'LICENSE-DIRECTORY-001',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tagId = DB::table('notification_tags')->insertGetId([
            'name' => 'Cobranza',
            'slug' => 'cobranza',
            'color' => '#c2410c',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contactId = DB::table('license_notifications')->insertGetId([
            'client_id' => $firstClient,
            'license_id' => $licenseId,
            'name' => 'Contacto Principal',
            'value' => '3000000000',
            'type' => 'phone',
            'phone' => '3000000000',
            'channels' => json_encode(['sms', 'whatsapp']),
            'active' => 1,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('license_notifications')->insert([
            'client_id' => $secondClient,
            'name' => 'Contacto Excluido',
            'value' => 'otro@example.test',
            'type' => 'email',
            'email' => 'otro@example.test',
            'channels' => json_encode(['email']),
            'active' => 1,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('notification_contact_tag')->insert([
            'contact_id' => $contactId,
            'tag_id' => $tagId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('license_notifications')->insert([
            'client_id' => $firstClient,
            'name' => 'Contacto A',
            'value' => '3000000001',
            'type' => 'phone',
            'phone' => '3000000001',
            'channels' => json_encode(['sms']),
            'active' => 1,
            'position' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new notifications_controller())->NotificationContact_GetDirectoryPage([
            'pagination' => ['page' => 1, 'per_page' => 20],
            'client_ids' => [$firstClient],
            'license_ids' => [$licenseId],
            'types' => ['phone'],
            'channels' => ['whatsapp'],
            'tag_ids' => [$tagId],
            'search' => 'Principal',
        ]);

        $this->assertSame(1, $response['status']);
        $this->assertSame(1, $response['pagination']['total']);
        $this->assertSame('Contacto Principal', $response['contacts'][0]['name']);
        $this->assertSame('Licencia Directorio', $response['contacts'][0]['license_name']);
        $this->assertSame('storage/images/erp/clients/cliente-directorio.png', $response['contacts'][0]['client']['photo_path']);
        $this->assertSame(['sms', 'whatsapp'], $response['contacts'][0]['channels']);
        $this->assertSame(['Cobranza'], collect($response['contacts'][0]['tags'])->pluck('name')->all());

        $ordered = (new notifications_controller())->NotificationContact_GetDirectoryPage([
            'pagination' => ['page' => 1, 'per_page' => 20],
        ]);
        $this->assertSame(['Contacto A', 'Contacto Principal', 'Contacto Excluido'], collect($ordered['contacts'])->pluck('name')->all());

        $exported = (new notifications_controller())->NotificationContact_GetDirectoryExport([
            'pagination' => ['page' => 1, 'per_page' => 1],
        ]);
        $this->assertSame(1, $exported['status']);
        $this->assertCount(3, $exported['contacts']);
        $export = new contacts_directory($exported['contacts']);
        $this->assertSame(['ID', 'Nombre', 'Valor', 'Tipo', 'Canales', 'Cliente ID', 'Cliente', 'Licencia ID', 'Licencia', 'Etiquetas', 'Estado'], $export->headings());
        $this->assertCount(3, $export->collection());

        $updated = (new notifications_controller())->NotificationContact_UpdateDirectory($contactId, [
            'name' => 'Contacto Editado',
            'type' => 'email',
            'value' => 'editado@example.test',
            'channels' => ['email'],
            'client_id' => $firstClient,
            'license_id' => '',
            'tag_ids' => [$tagId],
            'active' => 0,
        ]);
        $this->assertSame(1, $updated['status']);
        $this->assertSame('Contacto Editado', $updated['contact']['name']);
        $this->assertSame('email', $updated['contact']['type']);
        $this->assertSame('editado@example.test', $updated['contact']['value']);
        $this->assertFalse($updated['contact']['active']);

        $toggled = (new notifications_controller())->NotificationContact_ToggleDirectoryStatus($contactId, true);
        $this->assertSame(1, $toggled['status']);
        $this->assertTrue($toggled['contact']['active']);

        $imported = (new notifications_controller())->NotificationContact_ImportDirectory([
            [
                'id' => $contactId,
                'name' => 'Contacto Importado',
                'value' => 'importado@example.test',
                'type' => 'email',
                'channels' => 'email',
                'client_id' => $firstClient,
                'license_id' => '',
                'tags' => 'Cobranza',
                'active' => 'Inactivo',
            ],
            [
                'id' => '',
                'name' => 'Nuevo Sin ID',
                'value' => '3000000002',
                'type' => 'phone',
                'channels' => 'sms,whatsapp',
                'client_id' => $firstClient,
                'license_id' => '',
                'active' => 'Activo',
            ],
            [
                'name' => 'Nuevo Importado',
                'value' => 'nuevo@example.test',
                'type' => 'email',
                'channels' => 'email',
                'client_id' => $secondClient,
                'tags' => 'cobranza',
                'active' => 'Activo',
            ],
        ]);
        $this->assertSame(1, $imported['status']);
        $this->assertSame(2, $imported['created']);
        $this->assertSame(1, $imported['updated']);
        $this->assertSame([], $imported['errors']);
        $this->assertSame('Contacto Importado', DB::table('license_notifications')->where('id', $contactId)->value('name'));
        $this->assertSame(5, DB::table('license_notifications')->count());

        $deleted = (new notifications_controller())->delete_contact_directory(
            Request::create('/', 'POST', ['id' => $contactId])
        );
        $this->assertSame(1, $deleted['status']);
        $this->assertNotNull(DB::table('license_notifications')->where('id', $contactId)->value('deleted_at'));
        $visibleAfterDelete = (new notifications_controller())->NotificationContact_GetDirectoryPage([
            'pagination' => ['page' => 1, 'per_page' => 20],
        ]);
        $this->assertNotContains('Contacto Importado', collect($visibleAfterDelete['contacts'])->pluck('name')->all());
    }
}
