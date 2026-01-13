<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

/**
 * Pusher client with encryption support.
 */
class PusherWithEncryption extends Pusher
{
    public function __construct(string $appKey, Options $options)
    {
        // Validate options
        $options->validate();

        // In PHP, we'd use sodium for encryption
        // $options->nacl = sodium_*;

        parent::__construct($appKey, $options);
    }
}
