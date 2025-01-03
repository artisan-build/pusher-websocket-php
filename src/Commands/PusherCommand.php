<?php

namespace ArtisanBuild\Pusher\Commands;

use Illuminate\Console\Command;

class PusherCommand extends Command
{
    public $signature = 'pusher-websocket-php';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
