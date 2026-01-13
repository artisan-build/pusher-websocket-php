<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

interface URLScheme
{
    public function getInitial(string $key, mixed $params): string;

    public function getPath(string $key, mixed $options): ?string;
}
