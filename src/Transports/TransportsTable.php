<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

class TransportsTable
{
    public function __construct(
        public ?Transport $ws = null,
        public ?Transport $xhr_streaming = null,
        public ?Transport $xdr_streaming = null,
        public ?Transport $xhr_polling = null,
        public ?Transport $xdr_polling = null,
        public ?Transport $sockjs = null,
    ) {}
}
