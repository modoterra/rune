<?php

namespace Modoterra\Rune;

final class MemoizedEffect extends Effect
{
  private ?Outcome $outcome = null;

  public function run(): Outcome
  {
    return $this->outcome ??= parent::run();
  }

  public function memoize(): Effect
  {
    return $this;
  }
}
