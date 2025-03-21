<?php

namespace UntitledDevelopers\KockatoosAdminCore;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->name('kockatoos-admin-core');
//            ->hasConfigFile()
//            ->hasViews()
//            ->hasMigration('create_migration_table_name_table')
//            ->hasCommand(SkeletonCommand::class);

        $this->publishes([
            __DIR__.'/../resources/stubs/FilesController.php.stub' => app_path('Http/Controllers/FilesController.php'),
        ], 'skeleton-files-controller');
    }
}
