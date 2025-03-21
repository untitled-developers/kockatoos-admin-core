<?php

namespace UntitledDevelopers\KockatoosAdminCore\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider;
use UntitledDevelopers\KockatoosAdminCore\CoreServiceProvider;
use UntitledDevelopers\KockatoosAdminCore\Http\Services\FileService;

class TestCase extends Orchestra
{
    public FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'UntitledDevelopers\\KockatoosAdminCore\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            CoreServiceProvider::class,
            ImageOptimizerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
