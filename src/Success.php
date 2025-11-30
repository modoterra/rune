<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use RuntimeException;
use Throwable;

/**
 * @template-covariant V
 * @extends Outcome<V>
 */
class Success extends Outcome
{
  /**
   * @template T
   * @param T $value
   * @return self<T>
   */
  public static function from(mixed $value): self
  {
    return new Success($value);
  }

  public function didSucceed(): bool
  {
    return true;
  }

  public function didFail(): bool
  {
    return false;
  }

  /**
   * @template T
   * @param callable(V): T $mapper
   * @return self<T>
   */
  public function map(callable $mapper): self
  {
    return self::from($mapper($this->value()));
  }

  /**
   * @param callable(Throwable): Throwable $mapper
   * @return self<never>
   */
  public function mapError(callable $mapper): self
  {
    return $this;
  }

  /**
   * @param callable(mixed): mixed $mapper
   */
  public function flatMap(callable $mapper): mixed
  {
    return $mapper($this->value());
  }

  public function getOrElse(mixed $value): mixed
  {
    return $this->value();
  }

  /**
   * @param ?callable(mixed): mixed $onSuccess
   * @param ?callable(Throwable): Throwable $onFailure
   */
  public function fold(?callable $onSuccess = null, ?callable $onFailure = null): mixed
  {
    $onSuccess ??= fn (mixed $value) => $value;

    return $onSuccess($this->value());
  }

  public function value(): mixed
  {
    return $this->value;
  }

  public function error(): Throwable
  {
    throw new RuntimeException("Cannot get error from Success");
  }
}
