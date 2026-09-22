<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('license_notifications')
            ->select(['id', 'channels'])
            ->whereNotNull('channels')
            ->orderBy('id')
            ->chunkById(500, function ($contacts) use ($now): void {
                foreach ($contacts as $contact) {
                    $channels = json_decode((string) $contact->channels, true);
                    if (!is_array($channels)) {
                        continue;
                    }

                    $channels = array_values(array_unique(array_map(
                        static fn ($channel): string => strtolower(trim((string) $channel)),
                        $channels
                    )));
                    if (!in_array('sms', $channels, true) || in_array('whatsapp', $channels, true)) {
                        continue;
                    }

                    $channels[] = 'whatsapp';
                    DB::table('license_notifications')
                        ->where('id', $contact->id)
                        ->update([
                            'channels' => json_encode($channels),
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
    }
};