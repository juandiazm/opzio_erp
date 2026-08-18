<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\traits\mail_trait;


class db_backup extends Command
{
    use mail_trait;
    
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
    public function handle(): int
    {
        $backupPath = storage_path() . "/backups";
        $filename = 'opzio_erp_'.Carbon::now()->format('d').'.sql';
        $localFile = $backupPath . DIRECTORY_SEPARATOR . $filename;
        $errorFile = $localFile . '.error';

        try {
            /////////////////////
            /*if(Carbon::now()->format('i') == '00'){
                $this->Bill_SendMissingBillsNotification(2);
            }*/
            /////////////////////
            if (!file_exists($backupPath)) {
                if (!mkdir($backupPath, 0755, true) && !is_dir($backupPath)) {
                    throw new \RuntimeException("Could not create backup directory: {$backupPath}");
                }
            }
            $mysqldump = env('MYSQLDUMP_PATH', 'mysqldump');
            if (PHP_OS_FAMILY === 'Windows' && $mysqldump === 'mysqldump') {
                $paths = glob('C:\\wamp64\\bin\\mysql\\*\\bin\\mysqldump.exe');
                $mysqldump = !empty($paths) ? $paths[0] : $mysqldump;
            }
            $command = escapeshellarg($mysqldump)
                . ' --user=' . escapeshellarg((string) env('DB_USERNAME'))
                . ' --password=' . escapeshellarg((string) env('DB_PASSWORD'))
                . ' --host=' . escapeshellarg((string) env('DB_HOST'))
                . ' ' . escapeshellarg((string) env('DB_DATABASE'))
                . ' > ' . escapeshellarg($localFile)
                . ' 2> ' . escapeshellarg($errorFile);
            $returnVar = 0;
            $output = [];
            Log::info('ERP BACKUP: starting database dump', [
                'file' => $localFile,
                'mysqldump' => $mysqldump,
            ]);
            exec($command, $output, $returnVar);
            $errorOutput = is_file($errorFile) ? trim((string) file_get_contents($errorFile)) : '';
            if ($returnVar !== 0 || !is_file($localFile) || filesize($localFile) === 0) {
                if (is_file($localFile)) {
                    unlink($localFile);
                }
                throw new \RuntimeException('Database dump failed: ' . ($errorOutput ?: implode(PHP_EOL, $output) ?: 'no error output'));
            }
            if (is_file($errorFile)) {
                unlink($errorFile);
            }
            Log::info('ERP BACKUP: local dump created', [
                'file' => $localFile,
                'bytes' => filesize($localFile),
            ]);
            //if(Carbon::now()->format('H') == '00'){
                $filename_google = 'opzio_erp_'.Carbon::now()->format('d');
                $googleFolder   = 'Departamento I.T/Backups/Opzio erp';
                $this->ensureGoogleFolder($googleFolder);
                Log::info('ERP BACKUP: Google Drive folder checked', ['folder' => $googleFolder]);
                //Remove files with the same name
                $files = Storage::disk('google')->files($googleFolder);
                $replacedFiles = 0;
                foreach($files as $filePath){
                    if(pathinfo($filePath, PATHINFO_FILENAME) === $filename_google){
                        if (Storage::disk('google')->delete($filePath)) {
                            $replacedFiles++;
                        }
                    }
                }
                Log::info('ERP BACKUP: previous Google Drive files removed', [
                    'folder' => $googleFolder,
                    'files_deleted' => $replacedFiles,
                ]);
                $filename_google = $filename_google.'.sql';
                $fileHandle = fopen($localFile, 'rb');
                if ($fileHandle === false) {
                    throw new \RuntimeException("Could not open local backup: {$localFile}");
                }
                try {
                    $uploaded = Storage::disk('google')->put($googleFolder.'/'.$filename_google, $fileHandle);
                } finally {
                    // Some Flysystem adapters close the stream after uploading.
                    if (is_resource($fileHandle)) {
                        fclose($fileHandle);
                    }
                }
                if ($uploaded !== true) {
                    throw new \RuntimeException('Google Drive adapter did not confirm the upload.');
                }
                Log::info('ERP BACKUP: upload completed', [
                    'disk' => 'google',
                    'file' => $googleFolder.'/'.$filename_google,
                ]);
                // Get all files in a directory
                $files =   Storage::disk('backups')->allFiles();
                // Delete Files
                Storage::disk('backups')->delete($files);
                Log::info('ERP BACKUP: local backups cleaned', ['files_deleted' => count($files)]);
            //}
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('ERP BACKUP: failed', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'local_file' => $localFile,
            ]);
            try {
                $this->SendMail(['subject' => 'Opzio ERP backup failed'], [['address' => 'info@opzio.co', 'name' => 'Soporte Opzio']], 'emails.backup_failed', ['project' => 'Opzio ERP', 'error' => $exception->getMessage()], null);
            } catch (\Throwable $mailException) {
                Log::error('ERP BACKUP: failure notification failed', ['message' => $mailException->getMessage()]);
            }
            $this->error('ERP BACKUP failed: ' . $exception->getMessage());
            return self::FAILURE;
        }
    }

    private function ensureGoogleFolder(string $googleFolder): void
    {
        $disk = Storage::disk('google');
        $currentPath = '';

        foreach (array_filter(explode('/', trim($googleFolder, '/'))) as $folder) {
            $currentPath = $currentPath === '' ? $folder : $currentPath . '/' . $folder;
            if ($disk->makeDirectory($currentPath) === false) {
                throw new \RuntimeException("Could not create or access Google Drive folder: {$currentPath}");
            }
        }
    }
}
