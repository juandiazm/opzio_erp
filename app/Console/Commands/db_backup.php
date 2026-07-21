<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


class db_backup extends Command
{
    
    protected $signature = 'db:backup';
    protected $description = 'Make a backup of the db';
    protected $process;
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            /////////////////////
            /*if(Carbon::now()->format('i') == '00'){
                $this->Bill_SendMissingBillsNotification(2);
            }*/
            /////////////////////
            $backupPath = storage_path() . "/backups";
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            $filename = 'opzio_erp_'.Carbon::now()->format('d').'.sql';
            $command = "mysqldump --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " " . env('DB_DATABASE') . "  > " . $backupPath . "/" . $filename;
            $returnVar = NULL;
            $output = NULL;
            exec($command, $output, $returnVar);
            //if(Carbon::now()->format('H') == '00'){
                $filename_google = 'opzio_erp_'.Carbon::now()->format('d');
                //Remove files with the same name
                $files = Storage::disk('google')->files('');
                foreach($files as $filePath){
                    if(pathinfo($filePath, PATHINFO_FILENAME) === $filename_google){
                        Storage::disk('google')->delete($filePath);
                    }
                }
                $filename_google = $filename_google.'.sql';
                Storage::disk('google')->put($filename_google, fopen($backupPath . "/" . $filename, 'r+'));
                // Get all files in a directory
                $files =   Storage::disk('backups')->allFiles();
                // Delete Files
                Storage::disk('backups')->delete($files);
            //}
        } catch (ProcessFailedException $exception) {
            info('ERP BACKUP =>'.$exception);
        }
    }
}
