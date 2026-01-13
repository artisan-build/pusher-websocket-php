<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

interface RequestHooks
{
    public function getRequest(HTTPRequest $request): Ajax;

    public function abortRequest(Ajax $request): void;
}
