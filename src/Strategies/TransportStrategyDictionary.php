<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

/**
 * Dictionary of transport strategies keyed by name.
 *
 * @template-extends \ArrayObject<string, TransportStrategy>
 */
class TransportStrategyDictionary extends \ArrayObject {}
