<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Config;
use ArtisanBuild\Pusher\Errors\UnsupportedStrategy;
use ArtisanBuild\Pusher\Errors\UnsupportedTransport;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use ArtisanBuild\Pusher\Transports\TransportManager;
use Closure;

class StrategyBuilder
{
    public static function defineTransport(
        Config $config,
        string $name,
        string $type,
        int $priority,
        StrategyOptions $options,
        ?TransportManager $manager = null
    ): Strategy {
        $transports = Runtime::getTransports();
        $transportClass = $transports[$type] ?? null;

        if (! $transportClass) {
            throw new UnsupportedTransport($type);
        }

        $enabled =
            (! $config->enabledTransports ||
                in_array($name, $config->enabledTransports, true)) &&
            (! $config->disabledTransports ||
                ! in_array($name, $config->disabledTransports, true));

        if ($enabled) {
            $options->ignoreNullOrigin = $config->ignoreNullOrigin;

            return new TransportStrategy(
                $name,
                $priority,
                $manager ? $manager->getAssistant($transportClass) : $transportClass,
                $options
            );
        }

        return new UnsupportedStrategyImpl;
    }
}

/**
 * Strategy implementation that always returns unsupported.
 */
class UnsupportedStrategyImpl implements Strategy
{
    public function isSupported(): bool
    {
        return false;
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        // Defer the callback
        $callback(new UnsupportedStrategy);

        return new class implements StrategyRunner
        {
            public function abort(): void {}

            public function forceMinPriority(int $number): void {}
        };
    }
}
