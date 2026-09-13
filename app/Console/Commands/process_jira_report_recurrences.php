<?php

namespace App\Console\Commands;

use App\traits\jira_reports_trait;
use Illuminate\Console\Command;

class process_jira_report_recurrences extends Command
{
    use jira_reports_trait;

    protected $signature = 'jira:process-recurrences';

    protected $description = 'Genera los reportes Jira recurrentes que esten pendientes';

    public function handle(): int
    {
        $result = $this->Jira_ProcessReportRecurrences();
        $this->info('Recurrencias procesadas: '.$result['processed'].', generadas: '.$result['generated'].', fallidas: '.$result['failed']);

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}