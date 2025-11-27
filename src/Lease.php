<?php

declare(strict_types=1);

namespace Modoterra\Rune;

class Lease
{
  private bool $cancelled = false;
  private array $hooks = [];

  public function cancel(): void
  {
    if ($this->cancelled) return;
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

  public function hook(callable $callback): void
  {
    $this->hooks[] = $callback;
  }
}
