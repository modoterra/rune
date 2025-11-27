<?php

declare(strict_types=1);

namespace Modoterra\Rune;

class Scheduler
{
  private bool $running = false;

  /**
   * @var Enactor[]
   */
  private array $queue = [];

  /**
   * @var Timer[]
   */
  private array $timers = [];

  public function schedule(Enactor $enactor): void
  {
    $this->queue[] = $enactor;
  }

  public function loop(): void
  {
    $this->running = true;
    while ($this->running && $this->hasWork()) {
      $this->tick();
    }
  }

  private function tick(): void
  {
    $this->processTimers();
    $this->processEnactors();
  }

  private function processTimers(): void
  {
    $now = microtime(true);
    $pending = [];

    foreach ($this->timers as $timer) {
      if ($timer->isDue($now)) {
        $timer->execute();
      } else {
        $pending[] = $timer;
      }
    }

    $this->timers = $pending;
  }

  private function processEnactors(): void
  {
    $incomplete = [];

    foreach ($this->queue as $enactor) {
      if ($enactor->isComplete()) {
        continue;
      }

      if ($enactor->isCancelled()) {
        continue;
      }

      $enactor->activate();

      if (!$enactor->isComplete()) {
        $incomplete[] = $enactor;
      }
    }

    $this->queue = $incomplete;
  }

  private function hasWork(): bool
  {
    return !empty($this->queue);
  }
}
