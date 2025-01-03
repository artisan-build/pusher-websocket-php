<?php

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Commands\PusherCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
