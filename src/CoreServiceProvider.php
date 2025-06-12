<?php

namespace UntitledDevelopers\KockatoosAdminCore;

use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use UntitledDevelopers\KockatoosAdminCore\Components\CodePreview;
use UntitledDevelopers\KockatoosAdminCore\Components\Layout;
use UntitledDevelopers\KockatoosAdminCore\Components\Skyforge;
use UntitledDevelopers\KockatoosAdminCore\Components\TableDetails;
use UntitledDevelopers\KockatoosAdminCore\Components\TableDetailsLayout;
use UntitledDevelopers\KockatoosAdminCore\Components\TableHeader;
use UntitledDevelopers\KockatoosAdminCore\Services\FileService;

class CoreServiceProvider extends PackageServiceProvider
{


    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('kockatoos-admin-core')
            ->hasRoutes(['api', 'web'])
            ->hasConfigFile(['login'])
            ->hasViews();
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'kockatoos-admin-core-migrations');

//            ->hasMigration('create_migration_table_name_table')
//            ->hasCommand(SkeletonCommand::class);

//        $this->publishes([
//            __DIR__.'/../resources/stubs/FilesController.php.stub' => app_path('Http/Controllers/FilesController.php'),
//        ], 'skeleton-files-controller');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService(
                'public'
            );
        });
    }

    public function packageBooted(): void
    {
        Blade::component('kockatoos-admin-core::components.skyforge.layout', Layout::class);
        Blade::component('kockatoos-admin-core::components.skyforge.skyforge', Skyforge::class);
        Blade::component('kockatoos-admin-core::components.skyforge.code-preview', CodePreview::class);
        Blade::component('kockatoos-admin-core::components.skyforge.table-details', TableDetails::class);
        Blade::component('kockatoos-admin-core::components.skyforge.table-details-layout', TableDetailsLayout::class);
        Blade::component('kockatoos-admin-core::components.skyforge.table-header', TableHeader::class);
    }
}

// To publish migrations
//php artisan vendor:publish --tag=your-package-name-migrations
