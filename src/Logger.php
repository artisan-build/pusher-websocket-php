<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

// import { stringify } from './utils/collections';
use ArtisanBuild\Pusher\Utils\Collections;
use Closure;
use RuntimeException;

class Logger
{
    public function debug(array ...$args)
    {
        $this->log($this->globalLog(...), $args);
    }

    public function warn(array ...$args)
    {
        $this->log($this->globalLogWarn(...), $args);
    }

    public function error(array ...$args)
    {
        $this->log($this->globalLogError(...), $args);
    }

    private function globalLog(string $message)
    {
        // if (global.console && global.console.log) {
        //   global.console.log(message);
        // }
        throw new RuntimeException("Not Implemented Yet.\n\nMessage: {$message}");
    }

    private function globalLogWarn(string $message)
    {
        // if (global.console && global.console.warn) {
        //   global.console.warn(message);
        // } else {
        //   this.globalLog(message);
        // }
        throw new RuntimeException("Not Implemented Yet.\n\nMessage: {$message}");
    }

    private function globalLogError(string $message)
    {
        // if (global.console && global.console.error) {
        //   global.console.error(message);
        // } else {
        //   this.globalLogWarn(message);
        // }
        throw new RuntimeException("Not Implemented Yet.\n\nMessage: {$message}");
    }

    /**
     * @param  callable(string $message): void  $defaultLoggingFunction
     */
    private function log(
        Closure $defaultLoggingFunction,
        array ...$args,
    ) {
        // var message = stringify.apply(this, arguments);
        // if (Pusher.log) {
        //   Pusher.log(message);
        // } else if (Pusher.logToConsole) {
        //   const log = defaultLoggingFunction.bind(this);
        //   log(message);
        // }
        throw new RuntimeException('Not Implemented Yet.');
    }
}
