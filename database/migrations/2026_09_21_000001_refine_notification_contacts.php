<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('license_notifications', 'license_id')) {
            Schema::table('license_notifications', function (Blueprint $table): void {
                $table->dropForeign(['license_id']);
            });
        }

        Schema::table('license_notifications', function (Blueprint $table): void {
            if (!Schema::hasColumn('license_notifications', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('license_notifications', 'name')) {
                $table->string('name', 150)->nullable()->after('license_id');
            }
            if (!Schema::hasColumn('license_notifications', 'channels')) {
                $table->json('channels')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('license_notifications', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('active');
            }
        });

        Schema::table('license_notifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('license_id')->nullable()->change();
            if (!Schema::hasColumn('license_notifications', 'client_id')) {
                return;
            }
            $table->foreign('license_id')->references('id')->on('licenses')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->index('client_id');
            $table->index(['license_id', 'active']);
        });

        Schema::create('notification_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_contact_tag', function (Blueprint $table): void {
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();
            $table->primary(['contact_id', 'tag_id']);
            $table->foreign('contact_id')->references('id')->on('license_notifications')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('notification_tags')->cascadeOnDelete();
        });

        $now = now();
        $collectionTagId = DB::table('notification_tags')->insertGetId([
            'name' => 'Cobranza',
            'slug' => 'cobranza',
            'color' => '#c2410c',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $clients = DB::table('clients')->select(['id', 'name', 'lastname', 'email', 'phone'])->get();
        foreach ($clients as $client) {
            $email = trim((string) ($client->email ?? ''));
            $phone = trim((string) ($client->phone ?? ''));
            if ($email === '' && $phone === '') {
                continue;
            }

            $contactId = DB::table('license_notifications')
                ->where('client_id', $client->id)
                ->whereNull('license_id')
                ->whereNull('deleted_at')
                ->value('id');
            $channels = [];
            if ($email !== '') {
                $channels[] = 'email';
            }
            if ($phone !== '') {
                $channels[] = 'sms';
            }
            $data = [
                'client_id' => $client->id,
                'name' => trim(($client->name ?? '').' '.($client->lastname ?? '')) ?: 'Contacto principal',
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'channels' => json_encode($channels),
                'active' => 1,
                'position' => 0,
                'updated_at' => $now,
            ];
            if ($contactId) {
                DB::table('license_notifications')->where('id', $contactId)->update($data);
            } else {
                $data['created_at'] = $now;
                $contactId = DB::table('license_notifications')->insertGetId($data);
            }
            DB::table('notification_contact_tag')->insertOrIgnore([
                'contact_id' => $contactId,
                'tag_id' => $collectionTagId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $contacts = DB::table('license_notifications')->select([
            'id', 'license_id', 'client_id', 'email', 'phone', 'name', 'channels',
            'position', 'active', 'created_at', 'deleted_at',
        ])->get();
        foreach ($contacts as $contact) {
            $this->SplitNotificationContact($contact, $collectionTagId, $clients, $now);
        }
    }

    private function SplitNotificationContact(object $contact, int $collectionTagId, $clients, $now): void
    {
        $email = trim((string) ($contact->email ?? ''));
        $phone = trim((string) ($contact->phone ?? ''));
        $licenseClient = $contact->license_id
            ? DB::table('licenses')->where('id', $contact->license_id)->value('client_id')
            : $contact->client_id;
        $channels = json_decode((string) $contact->channels, true);
        $channels = is_array($channels) ? array_values(array_unique(array_map('strtolower', $channels))) : [];
        $phoneChannels = array_values(array_intersect($channels, ['sms', 'whatsapp', 'sms_whatsapp']));
        if ($phone !== '' && !$phoneChannels) {
            $phoneChannels = ['sms'];
        }
        $name = trim((string) $contact->name);
        if ($name === '' && $licenseClient) {
            $client = $clients->firstWhere('id', $licenseClient);
            $name = $client ? trim(($client->name ?? '').' '.($client->lastname ?? '')) : 'Contacto';
        }
        $name = $name !== '' ? $name : 'Contacto';
        $position = (int) $contact->position ?: (int) $contact->id;
        $tagIds = DB::table('notification_contact_tag')
            ->where('contact_id', $contact->id)
            ->pluck('tag_id')
            ->map(fn ($tagId) => (int) $tagId)
            ->all();
        $tagIds[] = $collectionTagId;

        $baseData = [
            'client_id' => $licenseClient,
            'name' => $name,
            'active' => $contact->active ?? 1,
            'updated_at' => $now,
        ];

        if ($email !== '') {
            DB::table('license_notifications')->where('id', $contact->id)->update(array_merge($baseData, [
                'email' => $email,
                'phone' => null,
                'channels' => json_encode(['email']),
                'position' => $position,
            ]));
        } elseif ($phone !== '') {
            DB::table('license_notifications')->where('id', $contact->id)->update(array_merge($baseData, [
                'email' => null,
                'phone' => $phone,
                'channels' => json_encode($phoneChannels),
                'position' => $position,
            ]));
        } else {
            DB::table('license_notifications')->where('id', $contact->id)->update(array_merge($baseData, [
                'channels' => json_encode([]),
                'position' => $position,
            ]));
        }

        if ($email !== '' && $phone !== '') {
            $phoneQuery = DB::table('license_notifications')
                ->where('id', '!=', $contact->id)
                ->whereNull('email')
                ->where('phone', $phone)
                ->whereNull('deleted_at');
            if ($contact->license_id === null) {
                $phoneQuery->whereNull('license_id');
            } else {
                $phoneQuery->where('license_id', $contact->license_id);
            }
            if ($licenseClient !== null) {
                $phoneQuery->where('client_id', $licenseClient);
            }
            $phoneContact = $phoneQuery->first();
            if ($phoneContact) {
                $phoneContactId = $phoneContact->id;
                DB::table('license_notifications')->where('id', $phoneContactId)->update([
                    'channels' => json_encode($phoneChannels),
                    'updated_at' => $now,
                ]);
            } else {
                $ownerQuery = DB::table('license_notifications');
                if ($contact->license_id === null) {
                    $ownerQuery->whereNull('license_id');
                } else {
                    $ownerQuery->where('license_id', $contact->license_id);
                }
                $phonePosition = max($position + 1, ((int) ($ownerQuery->max('position') ?? 0)) + 1);
                $phoneContactId = DB::table('license_notifications')->insertGetId([
                    'client_id' => $licenseClient,
                    'license_id' => $contact->license_id,
                    'name' => $name,
                    'email' => null,
                    'phone' => $phone,
                    'channels' => json_encode($phoneChannels),
                    'active' => $contact->active ?? 1,
                    'position' => $phonePosition,
                    'created_at' => $contact->created_at ?: $now,
                    'updated_at' => $now,
                    'deleted_at' => $contact->deleted_at,
                ]);
            }
            $this->SyncNotificationContactTags($phoneContactId, $tagIds, $now);
        }

        $this->SyncNotificationContactTags($contact->id, $tagIds, $now);
    }

    private function SyncNotificationContactTags(int $contactId, array $tagIds, $now): void
    {
        foreach (array_values(array_unique(array_map('intval', $tagIds))) as $tagId) {
            if ($tagId <= 0) {
                continue;
            }
            DB::table('notification_contact_tag')->insertOrIgnore([
                'contact_id' => $contactId,
                'tag_id' => $tagId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_contact_tag');
        Schema::dropIfExists('notification_tags');

        DB::table('license_notifications')->whereNull('license_id')->delete();
        Schema::table('license_notifications', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['license_id']);
            $table->dropIndex(['license_id', 'active']);
            $table->dropIndex(['client_id']);
            $table->dropColumn(['client_id', 'name', 'channels', 'position']);
            $table->unsignedBigInteger('license_id')->nullable(false)->change();
            $table->foreign('license_id')->references('id')->on('licenses');
        });
    }
};