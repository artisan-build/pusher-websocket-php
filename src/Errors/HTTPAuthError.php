<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Errors;

use Exception;

class HTTPAuthError extends Exception
{
    public function __construct(
        public readonly int $status,
        ?string $message = null,
    ) {
        parent::__construct($message);
        $this->status = $status;
    }
}
