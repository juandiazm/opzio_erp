<?php

namespace Tests\Unit;

use App\traits\multimedia_trait;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultimediaTraitTest extends TestCase
{
    public function test_trait_manages_files_on_the_configured_storage_disk()
    {
        Storage::fake('erp_media');

        $owner = new class {
            use multimedia_trait;

            public function store($contents, $directory, $filename)
            {
                return $this->Multimedia_Store($contents, $directory, $filename);
            }

            public function update($contents, $directory, $filename, $oldFilename)
            {
                return $this->Multimedia_Update($contents, $directory, $filename, $oldFilename);
            }

            public function get($directory, $filename)
            {
                return $this->Multimedia_Get($directory, $filename);
            }

            public function url($directory, $filename)
            {
                return $this->Multimedia_Url($directory, $filename);
            }

            public function path($directory, $filename)
            {
                return $this->Multimedia_Path($directory, $filename);
            }

            public function exists($directory, $filename)
            {
                return $this->Multimedia_Exists($directory, $filename);
            }

            public function delete($directory, $filename)
            {
                return $this->Multimedia_Delete($directory, $filename);
            }
        };

        $owner->store('original', 'clients', 'old.webp');
        $this->assertSame('original', $owner->get('clients', 'old.webp'));
        $this->assertTrue($owner->exists('clients', 'old.webp'));
        $this->assertStringContainsString('storage', $owner->url('clients', 'old.webp'));
        $this->assertStringContainsString('clients', $owner->path('clients', 'old.webp'));

        $owner->update('replacement', 'clients', 'new.webp', 'old.webp');
        Storage::disk('erp_media')->assertMissing('clients/old.webp');
        Storage::disk('erp_media')->assertExists('clients/new.webp');

        $owner->delete('clients', 'new.webp');
        Storage::disk('erp_media')->assertMissing('clients/new.webp');
    }
}