<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationContactSplitMigrationTest extends TestCase
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
            $table->timestamps();
        });
        Schema::create('licenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
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
    }

    public function test_migration_splits_email_and_phone_and_is_idempotent(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'Cliente Migrado',
            'lastname' => 'Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $licenseId = DB::table('licenses')->insertGetId([
            'client_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customTagId = DB::table('notification_tags')->insertGetId([
            'name' => 'VIP',
            'slug' => 'vip',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contactId = DB::table('license_notifications')->insertGetId([
            'client_id' => $clientId,
            'license_id' => $licenseId,
            'name' => 'Contacto Mixto',
            'email' => 'correo@example.test',
            'phone' => '3000000000',
            'channels' => json_encode(['email', 'sms', 'whatsapp']),
            'active' => 1,
            'position' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('notification_contact_tag')->insert([
            'contact_id' => $contactId,
            'tag_id' => $customTagId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_22_000001_split_notification_contact_channels.php');
        $migration->up();
        $migration->up();
        $normalizationMigration = require database_path('migrations/2026_09_22_000002_normalize_notification_contact_value.php');
        $normalizationMigration->up();

        $contacts = DB::table('license_notifications')->orderBy('id')->get();
        $this->assertCount(2, $contacts);
        $emailContact = $contacts->firstWhere('email', 'correo@example.test');
        $phoneContact = $contacts->firstWhere('phone', '3000000000');
        $this->assertSame($contactId, $emailContact->id);
        $this->assertSame('email', $emailContact->type);
        $this->assertSame('correo@example.test', $emailContact->value);
        $this->assertNull($emailContact->phone);
        $this->assertSame(['email'], json_decode($emailContact->channels, true));
        $this->assertSame('phone', $phoneContact->type);
        $this->assertSame('3000000000', $phoneContact->value);
        $this->assertNull($phoneContact->email);
        $this->assertSame(['sms', 'whatsapp'], json_decode($phoneContact->channels, true));
        $this->assertSame(
            [$customTagId, DB::table('notification_tags')->where('slug', 'cobranza')->value('id')],
            DB::table('notification_contact_tag')->where('contact_id', $phoneContact->id)->orderBy('tag_id')->pluck('tag_id')->all()
        );
    }
}