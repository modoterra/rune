<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use Fiber;
use RuntimeException;
use Throwable;

/**
 * @template V
 */
class Enactor
{
  /**
   * @var Fiber<void, void, Outcome<V>, void>
   */
  private Fiber $fiber;
  private Lease $lease;

  /**
   * @var ?Outcome<V>
   */
  private ?Outcome $outcome = null;

  /**
   * @template T
   * @param Effect<T> $effect
   * @param ?Lease $lease
   * @return Enactor<T>
   */
  public static function create(Effect $effect, ?Lease $lease = null): self
  {
    /** @var Fiber<void, void, Outcome<T>, void> */
    $fiber = new Fiber(fn () => $effect->run());

    return new self($fiber, $lease ?? new Lease());
  }

  public function activate(): void
  {
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

  public function await(): void
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

  /**
   * @param Fiber<void, void, Outcome<V>, void> $fiber
   */
  private function __construct(Fiber $fiber, Lease $lease)
  {
    $this->fiber = $fiber;
    $this->lease = $lease;
  }
}
