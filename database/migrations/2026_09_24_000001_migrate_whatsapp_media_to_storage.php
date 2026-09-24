<?php

use App\Models\whatsapp_message;
use App\Services\WhatsappMediaStorage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $storage = app(WhatsappMediaStorage::class);

        whatsapp_message::query()
            ->whereNotNull('media')
            ->orderBy('id')
            ->chunkById(100, function ($messages) use ($storage): void {
                foreach ($messages as $message) {
                    $media = is_array($message->media) ? $message->media : [];
                    if ($media === []) {
                        continue;
                    }

                    $storedMedia = $storage->storeMedia($media, (int) $message->id);
                    if ($storedMedia !== $media) {
                        $message->media = $storedMedia;
                        $message->save();
                    }
                }
            });
    }

    public function down(): void
    {
    }
};
