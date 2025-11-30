<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use Throwable;

/**
 * @template-covariant V
 */
abstract class Outcome
{
  protected Throwable $error;

  /**
   * @var V
   */
  protected readonly mixed $value;

  abstract public function didSucceed(): bool;

  abstract public function didFail(): bool;

  /**
   * @template T
   * @param callable(self<V>): self<T> $mapper
   * @return self<T>
   */
  abstract public function map(callable $mapper): self;

  /**
   * @param callable(Throwable): Throwable $mapper
   * @return self<never>
   */
  abstract public function mapError(callable $mapper): self;

  /**
   * @template T
   * @param callable(V): T $mapper
   * @return T
   */
  abstract public function flatMap(callable $mapper): mixed;

  abstract public function getOrElse(mixed $value): mixed;

  /**
   * @template T
   * @param ?callable(V): T $onSuccess
   * @param ?callable(Throwable): T $onFailure
   */
  abstract public function fold(?callable $onSuccess, ?callable $onFailure): mixed;

  /**
   * @return V
   */
  abstract public function value(): mixed;

  abstract public function error(): Throwable;

  /**
   * @param V $value
   */
  protected function __construct(mixed $value)
  {
    $this->value = $value;
  }
}
