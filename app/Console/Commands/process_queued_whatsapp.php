<?php

namespace App\Console\Commands;

use App\traits\whatsapp_notifications_trait;
use Illuminate\Console\Command;

class process_queued_whatsapp extends Command
{
    use whatsapp_notifications_trait;

    protected $signature = 'notifications:process-whatsapp';

    protected $description = 'Procesa mensajes WhatsApp templated pendientes';

    public function handle()
    {
        $response = $this->Notification_ProcessWhatsappQueue();
        $data = $response['data'];
        $this->info('WhatsApp procesados: '.$data['processed'].', enviados: '.$data['sent'].', fallidos: '.$data['failed']);

        return $data['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}