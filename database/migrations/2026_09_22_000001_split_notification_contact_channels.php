<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tagId = DB::table('notification_tags')->where('slug', 'cobranza')->value('id');
        if (!$tagId) {
            $now = now();
            $tagId = DB::table('notification_tags')->insertGetId([
                'name' => 'Cobranza',
                'slug' => 'cobranza',
                'color' => '#c2410c',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $clients = DB::table('clients')->select(['id', 'name', 'lastname'])->get();
        $now = now();
        $contacts = DB::table('license_notifications')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get([
                'id', 'license_id', 'client_id', 'email', 'phone', 'name', 'channels',
                'position', 'active', 'created_at', 'deleted_at',
            ]);

        foreach ($contacts as $contact) {
            $this->SplitNotificationContact($contact, (int) $tagId, $clients, $now);
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
        if (!$phoneChannels) {
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
            ->map(fn ($id) => (int) $id)
            ->all();
        $tagIds[] = $collectionTagId;

        DB::table('license_notifications')->where('id', $contact->id)->update([
            'client_id' => $licenseClient,
            'name' => $name,
            'email' => $email,
            'phone' => null,
            'channels' => json_encode(['email']),
            'position' => $position,
            'updated_at' => $now,
        ]);

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

        $this->SyncNotificationContactTags($contact->id, $tagIds, $now);
        $this->SyncNotificationContactTags($phoneContactId, $tagIds, $now);
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
    }
};