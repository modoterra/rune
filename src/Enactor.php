<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use Fiber;
use RuntimeException;
use Throwable;

class Enactor
{
  private Fiber $fiber;
  private ?Outcome $outcome = null;
  private ?Lease $lease = null;

  public static function create(Effect $effect): Enactor
  {
    $fiber = new Fiber(function () use ($effect) {
      return $effect->run();
    });

    return new Enactor($fiber);
  }

  public function activate(): void
  {
    if ($this->fiber->isTerminated()) {
      return;
    }

    if ($this->lease->isCancelled()) {
      $this->outcome = Failure::fromThrowable(new RuntimeException("Lease cancelled"));
      return;
    }

    try {
      if (!$this->fiber->isStarted()) {
        $this->fiber->start();
      } elseif ($this->fiber->isSuspended()) {
        $this->fiber->resume();
      }

      if ($this->fiber->isTerminated()) {
        $this->outcome = $this->fiber->getReturn();
      }
    } catch (Throwable $error) {
      $this->outcome = Failure::fromThrowable($error);
    }
  }

  public function await()
  {
    while (!$this->isComplete()) {
      $this->activate();
    }
  }

  public function isComplete(): bool
  {
    return $this->outcome !== null;
  }

  public function isCancelled(): bool
  {
    return $this->lease->isCancelled();
  }

  private function __construct(Fiber $fiber)
  {
    $this->fiber = $fiber;
  }
}
