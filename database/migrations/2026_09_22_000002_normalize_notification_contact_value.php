<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_notifications', function (Blueprint $table): void {
            if (!Schema::hasColumn('license_notifications', 'value')) {
                $table->string('value', 255)->nullable()->after('name');
            }
            if (!Schema::hasColumn('license_notifications', 'type')) {
                $table->string('type', 20)->nullable()->after('value');
            }
        });

        $contacts = DB::table('license_notifications')->get([
            'id', 'email', 'phone', 'channels', 'value', 'type',
        ]);
        foreach ($contacts as $contact) {
            $email = trim((string) ($contact->email ?? ''));
            $phone = trim((string) ($contact->phone ?? ''));
            $type = trim((string) ($contact->type ?? ''));
            $value = trim((string) ($contact->value ?? ''));

            if ($type === '' || $value === '') {
                $type = $email !== '' ? 'email' : 'phone';
                $value = $email !== '' ? $email : $phone;
            }

            $channels = json_decode((string) $contact->channels, true);
            $channels = is_array($channels) ? array_map('strtolower', $channels) : [];
            if (in_array('sms_whatsapp', $channels, true)) {
                $channels = array_merge($channels, ['sms', 'whatsapp']);
            }
            $channels = array_values(array_unique(array_intersect($channels, ['email', 'sms', 'whatsapp'])));
            $channels = $type === 'email' ? ['email'] : ($channels ?: ['sms']);

            DB::table('license_notifications')->where('id', $contact->id)->update([
                'value' => $value !== '' ? $value : null,
                'type' => in_array($type, ['email', 'phone'], true) ? $type : null,
                'channels' => json_encode($channels),
                'updated_at' => now(),
            ]);
        }

        Schema::table('license_notifications', function (Blueprint $table): void {
            $table->index(['type', 'value'], 'license_notifications_type_value_index');
        });
    }

    public function down(): void
    {
        Schema::table('license_notifications', function (Blueprint $table): void {
            $table->dropIndex('license_notifications_type_value_index');
            $table->dropColumn(['value', 'type']);
        });
    }
};