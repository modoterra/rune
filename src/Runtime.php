<?php

declare(strict_types=1);

namespace Modoterra\Rune;

class Runtime
{
  private Scheduler $scheduler;

  public static function default(): self
  {
    return new self(Scheduler::create());
  }

  /**
   * @template T
   * @param Effect<T> $effect
   * @param ?Lease $lease
   * @return Enactor<T>
   */
  public function schedule(Effect $effect, ?Lease $lease = null): Enactor
  {
    $enactor = Enactor::create($effect, $lease);
    $this->scheduler->schedule($enactor);

    return $enactor;
  }

  private function __construct(Scheduler $scheduler)
  {
    $this->scheduler = $scheduler;
  }
}
