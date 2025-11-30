<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use RuntimeException;
use Throwable;

/**
 * @extends Outcome<never>
 */
class Failure extends Outcome
{
  /**
   * @param Throwable $error
   * @return self
   */
  public static function fromThrowable(Throwable $error): self
  {
    return new self($error);
  }

  public function didSucceed(): bool
  {
    return false;
  }

  public function didFail(): bool
  {
    return true;
  }

  public function map(callable $mapper): self
  {
    return $this;
  }

  /**
   * @param callable(Throwable): Throwable $mapper
   */
  public function mapError(callable $mapper): self
  {
    return self::fromThrowable($mapper($this->error));
  }

  public function flatMap(callable $mapper): Throwable
  {
    return $this->error;
  }

  public function getOrElse(mixed $value): mixed
  {
    return $value;
  }

  public function fold(?callable $onSuccess, ?callable $onFailure = null): mixed
  {
    $onFailure ??= fn (mixed $error) => $error instanceof Throwable ? throw $error : $error;

    return $onFailure($this->error);
  }

  public function value(): mixed
  {
    throw new RuntimeException("Cannot get value from Failure");
  }

  public function error(): Throwable
  {
    return $this->error;
  }

  private function __construct(Throwable $error)
  {
    $this->error = $error;
  }
}
