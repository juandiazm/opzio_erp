<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\traits\notifications_trait;

class process_queued_sms extends Command
{
    use notifications_trait;

    protected $signature = 'notifications:process-sms';

    protected $description = 'Procesa SMS de notificaciones pendientes';

    public function handle()
    {
        $response = $this->Notification_ProcessSmsQueue();
        $data = $response['data'];
        $this->info('SMS procesados: '.$data['processed'].', enviados: '.$data['sent'].', fallidos: '.$data['failed']);

        return $data['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}