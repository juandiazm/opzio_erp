<?php

use App\Models\whatsapp_conversation;
use App\Models\whatsapp_message;
use App\traits\whatsapp_notifications_trait;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $renderer = new class
        {
            use whatsapp_notifications_trait;

            public function render(string $contentSid, array $contentVariables): string
            {
                return $this->Notification_WhatsappTemplateDisplayBody($contentSid, $contentVariables);
            }
        };

        $conversationIds = [];
        whatsapp_message::query()
            ->whereNotNull('content_sid')
            ->where(function ($query): void {
                $query->whereNull('body')
                    ->orWhere('body', '')
                    ->orWhere('body', 'like', 'Plantilla %');
            })
            ->orderBy('id')
            ->chunkById(100, function ($messages) use ($renderer, &$conversationIds): void {
                foreach ($messages as $message) {
                    $displayBody = $renderer->render(
                        (string) $message->content_sid,
                        is_array($message->content_variables) ? $message->content_variables : []
                    );
                    if ($displayBody === '') {
                        continue;
                    }

                    $message->body = $displayBody;
                    $message->save();
                    $conversationIds[(int) $message->conversation_id] = true;
                }
            });

        foreach (array_keys($conversationIds) as $conversationId) {
            $conversation = whatsapp_conversation::find($conversationId);
            if (!$conversation) {
                continue;
            }

            $latestMessage = $conversation->messages()->orderByDesc('id')->first();
            if (!$latestMessage || $latestMessage->direction !== 'outbound' || !$latestMessage->content_sid) {
                continue;
            }

            $preview = trim((string) $latestMessage->body);
            if ($preview === '' || preg_match('/^Plantilla\s+/i', $preview)) {
                continue;
            }

            if ($conversation->last_message_preview !== $preview) {
                $conversation->last_message_preview = $preview;
                $conversation->save();
            }
        }
    }

    public function down(): void
    {
    }
};
