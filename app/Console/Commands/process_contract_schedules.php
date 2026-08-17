<?php

namespace App\Console\Commands;

use App\traits\contracts_trait;
use Illuminate\Console\Command;

class process_contract_schedules extends Command
{
    use contracts_trait;

    protected $signature = 'contracts:process-schedules';

    protected $description = 'Genera y envia contratos de las programaciones vencidas';

    public function handle()
    {
        $response = $this->Contract_ProcessSchedules();
        $data = $response['data'];
        $this->info('Programaciones: '.$data['processed'].', contratos creados: '.$data['created'].', enviados: '.$data['sent'].', fallidos: '.$data['failed']);

        return $data['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}