<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

class MigrateErpPublicMediaToStorage extends Migration
{
    public function up()
    {
        $this->copyDirectory(public_path('images/erp'), storage_path('app/public/images/erp'), true);
        $this->removeEmptyDirectories(public_path('images/erp'));
        $this->copyDirectory(public_path('images/blog/segment'), storage_path('app/public/blog/segment'), true);
        $this->removeEmptyDirectories(public_path('images/blog/segment'));
    }

    public function down()
    {
        $this->copyDirectory(storage_path('app/public/images/erp'), public_path('images/erp'));
        $this->copyDirectory(storage_path('app/public/blog/segment'), public_path('images/blog/segment'));
    }

    private function copyDirectory($source, $destination, $deleteSource = false)
    {
        if (!File::isDirectory($source)) {
            return;
        }

        foreach (File::allFiles($source) as $file) {
            $relativePath = ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetDirectory = dirname($target);

            if (File::exists($target)) {
                if (hash_file('sha256', $file->getPathname()) !== hash_file('sha256', $target)) {
                    throw new \RuntimeException('Existe una colision de archivos al migrar '.$relativePath);
                }
                continue;
            }

            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                throw new \RuntimeException('No fue posible crear la carpeta de storage para '.$relativePath);
            }

            if (!File::copy($file->getPathname(), $target)) {
                throw new \RuntimeException('No fue posible migrar '.$relativePath);
            }
        }

        foreach (File::allFiles($source) as $file) {
            $relativePath = ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (!File::exists($target) || hash_file('sha256', $file->getPathname()) !== hash_file('sha256', $target)) {
                throw new \RuntimeException('No se pudo verificar la migracion de '.$relativePath);
            }
        }

        if ($deleteSource) {
            foreach (File::allFiles($source) as $file) {
                File::delete($file->getPathname());
            }
        }
    }

    private function removeEmptyDirectories($directory)
    {
        if (!File::isDirectory($directory)) {
            return;
        }

        $directories = File::allDirectories($directory);
        usort($directories, function ($left, $right) {
            return strlen($right) <=> strlen($left);
        });

        foreach ($directories as $childDirectory) {
            if (count(File::files($childDirectory)) === 0 && count(File::directories($childDirectory)) === 0) {
                File::deleteDirectory($childDirectory);
            }
        }

        if (count(File::files($directory)) === 0 && count(File::directories($directory)) === 0) {
            File::deleteDirectory($directory);
        }
    }
}