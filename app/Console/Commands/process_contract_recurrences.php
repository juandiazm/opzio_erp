<?php

namespace App\Console\Commands;

use App\traits\contracts_trait;
use Illuminate\Console\Command;

class process_contract_recurrences extends Command
{
    use contracts_trait;

    protected $signature = 'contracts:process-recurrences';

    protected $description = 'Crea los contratos de recurrencia vencidos';

    public function handle()
    {
        $response = $this->Contract_ProcessRecurrences();
        $data = $response['data'];
        $this->info('Recurrencias: '.$data['processed'].', contratos creados: '.$data['created'].', enviados: '.$data['sent'].', vencidos: '.$data['expired'].', fallidos: '.$data['failed']);

        return $data['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
