<?php

namespace UntitledDevelopers\KockatoosAdminCore\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider;
use UntitledDevelopers\KockatoosAdminCore\CoreServiceProvider;
use UntitledDevelopers\KockatoosAdminCore\Services\FileService;

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
            SanctumServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        config()->set('database.default', 'testing');
        config()->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ]);
        config()->set('auth.providers.admins', [
            'driver' => 'eloquent',
            'model' => \UntitledDevelopers\KockatoosAdminCore\Models\Admin::class,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
