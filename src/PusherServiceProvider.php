<?php

namespace ArtisanBuild\Pusher;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ArtisanBuild\Pusher\Commands\PusherCommand;

class PusherServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('pusher-websocket-php')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_pusher_websocket_php_table')
            ->hasCommand(PusherCommand::class);
    }
}
