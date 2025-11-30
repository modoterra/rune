<?php

declare(strict_types=1);

namespace Modoterra\Rune;

/**
 * @phpstan-type Hook callable(): void
 */
class Lease
{
  private bool $cancelled = false;

  /**
   * @var Hook[]
   */
  private array $hooks = [];

  public function cancel(): void
  {
    if ($this->cancelled) {
      return;
    }
    $this->cancelled = true;

    foreach ($this->hooks as $hook) {
      $hook();
    }

    $this->hooks = [];
  }

  public function isCancelled(): bool
  {
    return $this->cancelled;
  }

  /**
   * @param Hook $hook
   */
  public function hook(callable $hook): void
  {
    $this->hooks[] = $hook;
  }
}
