<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection\Handshake;

use ArtisanBuild\Pusher\Connection\Connection;
use ArtisanBuild\Pusher\Connection\Protocol\Action;
use ArtisanBuild\Pusher\Transports\TransportConnection;

class HandshakePayload extends Action
{
    public function __construct(
        string $action,
        ?string $id = null,
        ?int $activityTimeout = null,
        mixed $error = null,
        public ?TransportConnection $transport = null,
        public ?Connection $connection = null,
    ) {
        parent::__construct($action, $id, $activityTimeout, $error);
    }
}
