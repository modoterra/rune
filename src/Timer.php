<?php

declare(strict_types=1);

namespace Modoterra\Rune;

class Timer
{

  private float $dueTime;

  /**
   * @var callable
   */
  private $callback;

  public function __construct(float $dueTime, callable $callback)
  {
    $this->dueTime = $dueTime;
    $this->callback = $callback;
  }

  public function isDue(float $currentTime): bool
  {
    return $currentTime >= $this->dueTime;
  }

  public function execute(): void
  {
    ($this->callback)();
  }
}
