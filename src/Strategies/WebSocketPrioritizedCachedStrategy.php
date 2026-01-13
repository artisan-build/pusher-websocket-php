<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Runtimes\Runtime;
use ArtisanBuild\Pusher\Timeline\Timeline;
use ArtisanBuild\Pusher\Utils\Collections;
use Closure;

/**
 * Caches the last successful transport and, after the first few attempts,
 * uses the cached transport for subsequent attempts.
 */
class WebSocketPrioritizedCachedStrategy implements Strategy
{
    public Strategy $strategy;

    public TransportStrategyDictionary $transports;

    public int $ttl;

    public ?bool $usingTLS;

    public ?Timeline $timeline;

    public function __construct(
        Strategy $strategy,
        TransportStrategyDictionary $transports,
        StrategyOptions $options
    ) {
        $this->strategy = $strategy;
        $this->transports = $transports;
        $this->ttl = $options->ttl ?? 1800 * 1000;
        $this->usingTLS = $options->useTLS;
        $this->timeline = $options->timeline;
    }

    public function isSupported(): bool
    {
        return $this->strategy->isSupported();
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        $usingTLS = $this->usingTLS;
        $info = self::fetchTransportCache($usingTLS);
        $cacheSkipCount = $info && isset($info['cacheSkipCount']) ? $info['cacheSkipCount'] : 0;

        $strategies = [$this->strategy];
        if ($info && self::now() <= $info['timestamp'] + $this->ttl) {
            $transport = $this->transports[$info['transport']] ?? null;
            if ($transport) {
                if (in_array($info['transport'], ['ws', 'wss'], true) || $cacheSkipCount > 3) {
                    $this->timeline?->info([
                        'cached' => true,
                        'transport' => $info['transport'],
                        'latency' => $info['latency'],
                    ]);
                    $strategies[] = new SequentialStrategy(
                        [$transport],
                        new StrategyOptions(
                            timeout: $info['latency'] * 2 + 1000,
                            failFast: true
                        )
                    );
                } else {
                    $cacheSkipCount++;
                }
            }
        }

        $startTimestamp = self::now();
        $runnerHolder = new RunnerHolder;
        $minPriorityHolder = new MinPriorityHolder($minPriority);

        $cb = function (mixed $error, mixed $handshake) use (
            &$cb,
            $runnerHolder,
            &$strategies,
            &$startTimestamp,
            $usingTLS,
            $cacheSkipCount,
            $minPriorityHolder,
            $callback
        ): void {
            if ($error) {
                self::flushTransportCache($usingTLS);
                if (count($strategies) > 0) {
                    $startTimestamp = self::now();
                    $runnerHolder->runner = array_pop($strategies)->connect($minPriorityHolder->value, $cb);
                } else {
                    $callback($error);
                }
            } else {
                self::storeTransportCache(
                    $usingTLS,
                    $handshake->transport->name,
                    self::now() - $startTimestamp,
                    $cacheSkipCount
                );
                $callback(null, $handshake);
            }
        };

        $runnerHolder->runner = array_pop($strategies)->connect($minPriorityHolder->value, $cb);

        return new CachedStrategyRunner($runnerHolder, $minPriorityHolder);
    }

    private static function now(): int
    {
        return (int) (microtime(true) * 1000);
    }

    private static function getTransportCacheKey(?bool $usingTLS): string
    {
        return 'pusherTransport'.($usingTLS ? 'TLS' : 'NonTLS');
    }

    private static function fetchTransportCache(?bool $usingTLS): ?array
    {
        $storage = Runtime::getLocalStorage();
        if ($storage) {
            try {
                $serializedCache = $storage[self::getTransportCacheKey($usingTLS)] ?? null;
                if ($serializedCache) {
                    return json_decode($serializedCache, true);
                }
            } catch (\Throwable $e) {
                self::flushTransportCache($usingTLS);
            }
        }

        return null;
    }

    private static function storeTransportCache(
        ?bool $usingTLS,
        string $transport,
        int $latency,
        int $cacheSkipCount
    ): void {
        $storage = Runtime::getLocalStorage();
        if ($storage) {
            try {
                $storage[self::getTransportCacheKey($usingTLS)] = Collections::safeJSONStringify([
                    'timestamp' => self::now(),
                    'transport' => $transport,
                    'latency' => $latency,
                    'cacheSkipCount' => $cacheSkipCount,
                ]);
            } catch (\Throwable $e) {
                // catch over quota exceptions
            }
        }
    }

    private static function flushTransportCache(?bool $usingTLS): void
    {
        $storage = Runtime::getLocalStorage();
        if ($storage) {
            try {
                unset($storage[self::getTransportCacheKey($usingTLS)]);
            } catch (\Throwable $e) {
                // catch exceptions
            }
        }
    }
}
