<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Channels\Channel;
use ArtisanBuild\Pusher\Channels\Channels;
use ArtisanBuild\Pusher\Connection\ConnectionManager;
use ArtisanBuild\Pusher\Connection\ConnectionManagerOptions;
use ArtisanBuild\Pusher\Connection\Protocol\MessageTypes\PusherEvent;
use ArtisanBuild\Pusher\Events\Dispatcher as EventsDispatcher;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use ArtisanBuild\Pusher\Timeline\Timeline;
use ArtisanBuild\Pusher\Timeline\TimelineLevel;
use ArtisanBuild\Pusher\Timeline\TimelineOptions;
use ArtisanBuild\Pusher\Timeline\TimelineSender;
use ArtisanBuild\Pusher\Timeline\TimelineSenderOptions;
use ArtisanBuild\Pusher\Utils\Collections;
use ArtisanBuild\Pusher\Utils\Factory;
use ArtisanBuild\Pusher\Utils\Timers\PeriodicTimer;
use Closure;
use RuntimeException;

class Pusher
{
    /*  STATIC PROPERTIES */
    /**
     * @var Pusher[]
     */
    public static array $instances = [];

    public static bool $isReady = false;

    public static bool $logToConsole = false;

    // for jsonp
    // static Runtime: AbstractRuntime = Runtime;
    // static ScriptReceivers: any = (<any>Runtime).ScriptReceivers;
    // static DependenciesReceivers: any = (<any>Runtime).DependenciesReceivers;
    // static auth_callbacks: any = (<any>Runtime).auth_callbacks;

    public static function ready(): void
    {
        Pusher::$isReady = true;
        for ($i = 0, $l = count(Pusher::$instances); $i < $l; $i++) {
            Pusher::$instances[$i]->connect();
        }
    }

    // static log: (message: any) => void;

    /**
     * @return array<string>
     */
    private static function getClientFeatures(): array
    {
        return Collections::keys(
            // Collections.filterObject({ ws: Runtime.Transports.ws }, function(t) {
            //   return t.isSupported({});
            // })
        );
    }

    /* INSTANCE PROPERTIES */
    public string $key;

    public Config $config;

    public Channels $channels;

    public EventsDispatcher $global_emitter;

    public int $sessionID;

    public Timeline $timeline;

    public ?TimelineSender $timelineSender = null;

    public ConnectionManager $connection;

    public ?PeriodicTimer $timelineSenderTimer = null;

    public UserFacade $user;

    public function __construct(string $app_key, Options $options)
    {
        self::checkAppKey($app_key);
        Options::validateOptions($options);
        $this->key = $app_key;
        $this->config = new Config($options, $this);

        $this->channels = Factory::createChannels();
        $this->global_emitter = new EventsDispatcher;
        $this->sessionID = mt_rand(0, 1_000_000_000);

        $this->timeline = new Timeline($this->key, $this->sessionID, new TimelineOptions(
            cluster: $this->config->cluster,
            features: Pusher::getClientFeatures(),
            params: $this->config->timelineParams ?? [],
            limit: 50,
            level: TimelineLevel::INFO,
            version: Defaults::VERSION,
        ));

        if ($this->config->enableStats) {
            $this->timelineSender = Factory::createTimelineSender($this->timeline, new TimelineSenderOptions(
                host: $this->config->statsHost,
            ));
        }

        $getStrategy = fn (array $strategyOptions) => Runtime::getDefaultStrategy($this->config, $strategyOptions);

        $this->connection = Factory::createConnectionManager($this->key, new ConnectionManagerOptions(
            timeline: $this->timeline,
            getStrategy: $getStrategy,
            activityTimeout: $this->config->activityTimeout,
            pongTimeout: $this->config->pongTimeout,
            unavailableTimeout: $this->config->unavailableTimeout,
            useTLS: (bool) $this->config->useTLS,
        ));

        $this->connection->bind('connected', function (): void {
            $this->subscribeAll();
            if (isset($this->timelineSender)) {
                $this->timelineSender->send($this->connection->isUsingTLS());
            }
        });

        $this->connection->bind('message', function (mixed $event): void {
            // Event is already a PusherEvent object from Connection::bindListeners
            if ($event instanceof PusherEvent) {
                $pusherEvent = $event;
            } else {
                // Handle legacy array format
                $pusherEvent = new PusherEvent(
                    event: $event['event'] ?? '',
                    channel: $event['channel'] ?? null,
                    data: $event['data'] ?? null,
                    user_id: $event['user_id'] ?? null,
                );
            }

            $eventName = $pusherEvent->event;
            $internal = str_starts_with($eventName, 'pusher_internal:');
            $channelName = $pusherEvent->channel;

            if ($channelName) {
                $channel = $this->channel($channelName);
                if ($channel) {
                    $channel->handleEvent($pusherEvent);
                }
            }
            // Emit globally [deprecated]
            if (! $internal) {
                $this->global_emitter->emit($eventName, $pusherEvent->data);
            }
        });

        $this->connection->bind('connecting', function (): void {
            $this->channels->disconnect();
        });

        $this->connection->bind('disconnected', function (): void {
            $this->channels->disconnect();
        });

        $this->connection->bind('error', function (mixed $err): void {
            Logger::warn($err);
        });

        Pusher::$instances[] = $this;
        $this->timeline->info(['instances' => count(Pusher::$instances)]);

        $this->user = new UserFacade($this);

        if (self::$isReady) {
            $this->connect();
        }
    }

    public function channel(string $name): ?Channel
    {
        return $this->channels->find($name);
    }

    /**
     * @return Channel[]
     */
    public function allChannels(): array
    {
        return $this->channels->all();
    }

    public function connect(): void
    {
        $this->connection->connect();

        if ($this->timelineSender) {
            if (! $this->timelineSenderTimer) {
                $usingTLS = $this->connection->isUsingTLS();
                $timelineSender = $this->timelineSender;
                $this->timelineSenderTimer = new PeriodicTimer(
                    60_000,
                    fn () => $timelineSender->send($usingTLS),
                );
            }
        }
    }

    public function disconnect(): void
    {
        $this->connection->disconnect();

        if ($this->timelineSenderTimer) {
            $this->timelineSenderTimer->ensureAborted();
            $this->timelineSenderTimer = null;
        }
    }

    public function bind(
        string $event_name,
        Closure $callback,
        mixed $context = null,
    ): self {
        $this->global_emitter->bind($event_name, $callback, $context);

        return $this;
    }

    public function unbind(
        ?string $event_name = null,
        ?Closure $callback = null,
        mixed $context = null,
    ): self {
        $this->global_emitter->unbind($event_name, $callback, $context);

        return $this;
    }

    public function bind_global(Closure $callback): self
    {
        $this->global_emitter->bind_global($callback);

        return $this;
    }

    public function unbind_global(?Closure $callback = null): self
    {
        $this->global_emitter->unbind_global($callback);

        return $this;
    }

    public function unbind_all(?Closure $callback = null): self
    {
        $this->global_emitter->unbind_all();

        return $this;
    }

    public function subscribeAll(): void
    {
        foreach ($this->channels->all() as $channel) {
            $this->subscribe($channel->name);
        }
    }

    public function subscribe(string $channel_name): Channel
    {
        $channel = $this->channels->add($channel_name, $this);
        if ($channel->subscriptionPending && $channel->subscriptionCancelled) {
            $channel->reinstateSubscription();
        } elseif (
            ! $channel->subscriptionPending &&
            $this->connection->state === 'connected'
        ) {
            $channel->subscribe();
        }

        return $channel;
    }

    public function unsubscribe(string $channel_name): void
    {
        $channel = $this->channels->find($channel_name);
        if ($channel && $channel->subscriptionPending) {
            $channel->cancelSubscription();
        } else {
            $channel = $this->channels->remove($channel_name);
            if ($channel->subscribed) {
                $channel->unsubscribe();
            }
        }
    }

    public function send_event(string $event_name, mixed $data, ?string $channel = null): mixed
    {
        return $this->connection->sendEvent($event_name, $data, $channel);
    }

    public function shouldUseTLS(): bool
    {
        return $this->config->useTLS;
    }

    public function signin(): void
    {
        $this->user->signin();
    }

    public static function checkAppKey(mixed $key): void
    {
        if ($key === null || empty($key)) {
            throw new RuntimeException(
                'You must pass your app key when you instantiate Pusher.',
            );
        }
    }
}
