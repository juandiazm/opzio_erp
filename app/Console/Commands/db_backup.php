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
            $filename = 'ridder_erp_'.Carbon::now()->format('d').'.sql';
            $command = "mysqldump --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " " . env('DB_DATABASE') . "  > " . storage_path() . "/backups/" . $filename;
            $returnVar = NULL;
            $output = NULL;
            exec($command, $output, $returnVar);
            //if(Carbon::now()->format('H') == '00'){
                $filename_google = 'ridder_erp_'.Carbon::now()->format('d');
                //Remove files with the same name
                $files = collect(Storage::disk('google')->listContents());
                foreach($files as $file){
                    if($file['filename'] == $filename_google){
                        Storage::disk('google')->delete($file['basename']);
                    }
                }
                $filename_google = $filename_google.'.sql';
                Storage::disk('google')->put($filename_google, fopen(storage_path() . "/backups/" . $filename, 'r+'));
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
