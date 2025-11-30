<?php

declare(strict_types=1);

namespace Modoterra\Rune;

/**
 * @template V
 * @extends Effect<V>
 */
final class MemoizedEffect extends Effect
{
  /**
   * @var ?Outcome<V>
   */
  private ?Outcome $outcome = null;

  /**
   * @template T
   * @param callable(): Outcome<T> $thunk
   * @return self<T>
   */
  public static function fromThunk(callable $thunk): self
  {
    return new self($thunk);
  }

  /**
   * @return Outcome<V>
   */
  public function run(): Outcome
  {
    return $this->outcome ??= parent::run();
  }

  /**
   * @return self<V>
   */
  public function memoize(): self
  {
    return $this;
  }
}
