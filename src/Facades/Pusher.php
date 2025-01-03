<?php

namespace ArtisanBuild\Pusher\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ArtisanBuild\Pusher\Pusher
 */
class Pusher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ArtisanBuild\Pusher\Pusher::class;
    }
}
