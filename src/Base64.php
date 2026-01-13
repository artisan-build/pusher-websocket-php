<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

class Base64
{
    /**
     * Encode a string to base64.
     *
     * PHP has native base64_encode, but this class provides
     * compatibility with the JavaScript implementation.
     */
    public static function encode(string $s): string
    {
        return base64_encode($s);
    }

    public static function decode(string $s): string
    {
        return base64_decode($s);
    }
}
