<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

interface StrategyRunner
{
  public function forceMinPriority(int $number): void;
  public function abort(): void;
}
