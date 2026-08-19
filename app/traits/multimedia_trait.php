<?php

namespace App\traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait multimedia_trait
{
    protected function Multimedia_Disk($disk = 'erp_media')
    {
        return Storage::disk($disk);
    }

    protected function Multimedia_NormalizePath($directory, $filename)
    {
        $directory = trim(str_replace('\\', '/', (string) $directory), '/');
        $filename = ltrim(str_replace('\\', '/', (string) $filename), '/');

        if ($filename === '' || str_contains($directory, '..') || str_contains($filename, '..')) {
            throw new \InvalidArgumentException('La ruta multimedia no es valida');
        }

        return $directory === '' ? $filename : $directory.'/'.$filename;
    }

    protected function Multimedia_Store($contents, $directory, $filename, $disk = 'erp_media')
    {
        $path = $this->Multimedia_NormalizePath($directory, $filename);
        $filesystem = $this->Multimedia_Disk($disk);

        if (!$filesystem->put($path, $contents)) {
            throw new \RuntimeException('No fue posible guardar el archivo multimedia');
        }

        $this->Multimedia_Optimize($path, $disk);

        return $path;
    }

    protected function Multimedia_StoreImage($source, $directory, $filename, $orientate = false, $disk = 'erp_media')
    {
        $image = Image::make($source);
        if ($orientate) {
            $image->orientate();
        }

        $image->encode('webp', 90);

        return $this->Multimedia_Store($image->stream(), $directory, $filename, $disk);
    }

    protected function Multimedia_Update($contents, $directory, $filename, $oldFilename = null, $disk = 'erp_media')
    {
        $path = $this->Multimedia_Store($contents, $directory, $filename, $disk);

        if ($oldFilename !== null) {
            $oldPath = $this->Multimedia_NormalizePath($directory, $oldFilename);
            if ($oldPath !== $path) {
                $this->Multimedia_Disk($disk)->delete($oldPath);
            }
        }

        return $path;
    }

    protected function Multimedia_UpdateImage($source, $directory, $filename, $oldFilename = null, $orientate = false, $disk = 'erp_media')
    {
        $image = Image::make($source);
        if ($orientate) {
            $image->orientate();
        }

        $image->encode('webp', 90);

        return $this->Multimedia_Update($image->stream(), $directory, $filename, $oldFilename, $disk);
    }

    protected function Multimedia_Get($directory, $filename, $disk = 'erp_media')
    {
        return $this->Multimedia_Disk($disk)->get($this->Multimedia_NormalizePath($directory, $filename));
    }

    protected function Multimedia_Url($directory, $filename, $disk = 'erp_media')
    {
        return $this->Multimedia_Disk($disk)->url($this->Multimedia_NormalizePath($directory, $filename));
    }

    protected function Multimedia_Path($directory, $filename, $disk = 'erp_media')
    {
        return $this->Multimedia_Disk($disk)->path($this->Multimedia_NormalizePath($directory, $filename));
    }

    protected function Multimedia_Exists($directory, $filename, $disk = 'erp_media')
    {
        return $this->Multimedia_Disk($disk)->exists($this->Multimedia_NormalizePath($directory, $filename));
    }

    protected function Multimedia_Delete($directory, $filename, $disk = 'erp_media')
    {
        if ($filename === null || $filename === '') {
            return false;
        }

        return $this->Multimedia_Disk($disk)->delete($this->Multimedia_NormalizePath($directory, $filename));
    }

    private function Multimedia_Optimize($path, $disk = 'erp_media')
    {
        $optimizer = 'ImageOptimizer';
        if (!class_exists($optimizer)) {
            return;
        }

        try {
            $optimizer::optimize($this->Multimedia_Disk($disk)->path($path));
        } catch (\Throwable $exception) {
            info('Multimedia_Optimize error: '.$exception->getMessage());
        }
    }
}