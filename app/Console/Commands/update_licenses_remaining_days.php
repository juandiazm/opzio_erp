<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\traits\licenses_trait;

class update_licenses_remaining_days extends Command
{
    use licenses_trait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:update_licenses_remaining_days';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update licenses remaining days';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $Response = $this->Licenses_UpdateRemainingDays();
        return 0;
    }
}
