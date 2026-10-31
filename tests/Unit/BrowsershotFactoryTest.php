<?php

namespace Tests\Unit;

use App\Services\Pdf\BrowsershotFactory;
use App\Services\Pdf\ConfiguredBrowsershot;
use Illuminate\Support\Facades\File;
use ReflectionProperty;
use Tests\TestCase;

class BrowsershotFactoryTest extends TestCase
{
    public function test_it_uses_the_configured_shared_runtime_and_project_temp_directories(): void
    {
        $testRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'browsershot-factory-'.bin2hex(random_bytes(6));
        $modulesPath = $testRoot.DIRECTORY_SEPARATOR.'node_modules';
        $cachePath = $testRoot.DIRECTORY_SEPARATOR.'cache';
        mkdir($modulesPath.DIRECTORY_SEPARATOR.'puppeteer', 0700, true);
        mkdir($cachePath, 0700, true);
        file_put_contents($modulesPath.DIRECTORY_SEPARATOR.'puppeteer'.DIRECTORY_SEPARATOR.'package.json', '{}');

        $this->app['config']->set('services.pdf.node_binary', 'node-test');
        $this->app['config']->set('services.pdf.node_module_path', $modulesPath);
        $this->app['config']->set('services.pdf.puppeteer_cache_dir', $cachePath);
        $this->app['config']->set('services.pdf.no_sandbox', false);

        try {
            $browsershot = app(BrowsershotFactory::class)->fromHtml('<html><body>test</body></html>');
            $this->assertInstanceOf(ConfiguredBrowsershot::class, $browsershot);
            $this->assertSame($modulesPath, $this->propertyValue($browsershot, 'nodeModulePath'));
            $this->assertSame('node-test', $this->propertyValue($browsershot, 'nodeBinary'));
            $this->assertSame(storage_path('app/runtime/browsershot/tmp'), $this->propertyValue($browsershot, 'tempPath'));
            $this->assertSame($cachePath, $this->propertyValue($browsershot, 'nodeEnvVars')['PUPPETEER_CACHE_DIR']);
            $this->assertSame(storage_path('app/runtime/browsershot/home'), $this->propertyValue($browsershot, 'nodeEnvVars')['HOME']);
            $this->assertSame(storage_path('app/runtime/browsershot/config'), $this->propertyValue($browsershot, 'nodeEnvVars')['XDG_CONFIG_HOME']);
            $this->assertSame(storage_path('app/runtime/browsershot/cache'), $this->propertyValue($browsershot, 'nodeEnvVars')['XDG_CACHE_HOME']);
            $this->assertSame(storage_path('app/runtime/browsershot/data'), $this->propertyValue($browsershot, 'nodeEnvVars')['XDG_DATA_HOME']);
            $this->assertSame(storage_path('app/runtime/browsershot/tmp'), $this->propertyValue($browsershot, 'nodeEnvVars')['TMPDIR']);
            $this->assertSame(storage_path('app/runtime/browsershot/tmp'), $this->propertyValue($browsershot, 'nodeEnvVars')['TMP']);
            $this->assertSame(storage_path('app/runtime/browsershot/tmp'), $this->propertyValue($browsershot, 'nodeEnvVars')['TEMP']);
            $this->assertSame([], $this->propertyValue($browsershot, 'chromiumArguments'));

            $windowsEnvironment = $this->invokeProtected($browsershot, 'getWindowsEnv');
            $this->assertSame($modulesPath, $windowsEnvironment['NODE_PATH']);
            $this->assertSame($cachePath, $windowsEnvironment['PUPPETEER_CACHE_DIR']);

            $this->app['config']->set('services.pdf.no_sandbox', true);
            $productionBrowsershot = app(BrowsershotFactory::class)->fromHtml('<html><body>test</body></html>');
            $this->assertSame(
                ['--no-sandbox', '--disable-setuid-sandbox'],
                $this->propertyValue($productionBrowsershot, 'chromiumArguments')
            );
        } finally {
            File::deleteDirectory($testRoot);
        }
    }

    private function propertyValue(object $object, string $name): mixed
    {
        $property = new ReflectionProperty($object, $name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    private function invokeProtected(object $object, string $name): mixed
    {
        $method = new \ReflectionMethod($object, $name);
        $method->setAccessible(true);

        return $method->invoke($object);
    }
}
