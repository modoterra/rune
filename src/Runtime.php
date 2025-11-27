<?php

declare(strict_types=1);

namespace Modoterra\Rune;

class Runtime
{
  private Scheduler $scheduler;

  public static function default(): Runtime
  {
    return new Runtime(new Scheduler());
  }

  public function schedule(Effect $effect, ?Lease $lease = null): Enactor
  {
    $enactor = Enactor::create($effect);
    $this->scheduler->schedule($enactor, $lease);
    return $enactor;
  }

  private function __construct(Scheduler $scheduler)
  {
    $this->scheduler = $scheduler;
  }
}
