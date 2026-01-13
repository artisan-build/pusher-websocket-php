<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Timeline;

use ArtisanBuild\Pusher\Utils\Collections;
use Closure;

// import Util from '../util';

class Timeline
{
    public string $key;

    public int $session;

    public array $events;

    public TimelineOptions $options;

    public int $sent;

    public int $uniqueID;

    public function __construct(
        string $key,
        int $session,
        TimelineOptions $options = new TimelineOptions,
    ) {
        $this->key = $key;
        $this->session = $session;
        $this->events = [];
        $this->options = $options;
        $this->sent = 0;
        $this->uniqueID = 0;
    }

    public function log(TimelineLevel $level, mixed $event): void
    {
        if ($level->value <= $this->options->level->value) {
            $this->events[] = $event;
            // Collections::extend({}, event, { timestamp: Util.now() })
            if ($this->options->limit && count($this->events) > $this->options->limit) {
                array_shift($this->events);
            }
        }
    }

    public function error(mixed $event): void
    {
        $this->log(TimelineLevel::ERROR, $event);
    }

    public function info(mixed $event): void
    {
        $this->log(TimelineLevel::INFO, $event);
    }

    public function debug(mixed $event): void
    {
        $this->log(TimelineLevel::DEBUG, $event);
    }

    public function isEmpty(): bool
    {
        return count($this->events) === 0;
    }

    public function send(Closure $sendfn, Closure $callback): bool
    {
        $data = [
            'session' => $this->session,
            'bundle' => $this->sent + 1,
            'key' => $this->key,
            'lib' => 'php',
            'version' => $this->options->version,
            'cluster' => $this->options->cluster,
            'features' => $this->options->features,
            'timeline' => $this->events,
            ...$this->options->params,
        ];

        $this->events = [];
        $sendfn($data, function (mixed $error, mixed $result) use ($callback): void {
            if (! $error) {
                $this->sent++;
            }
            if ($callback) {
                $callback($error, $result);
            }
        });

        return true;
    }

    public function generateUniqueID(): int
    {
        $this->uniqueID++;

        return $this->uniqueID;
    }
}
