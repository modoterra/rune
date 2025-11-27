<?php

namespace Modoterra\Rune;

use Throwable;

class Failure extends Outcome
{
  public static function fromThrowable(Throwable $error): Failure
  {
    return new Failure($error);
  }

  public function didSucceed(): bool
  {
    return false;
  }

  public function didFail(): bool
  {
    return true;
  }

  public function map(callable $mapper): Outcome
  {
    return $this;
  }

  public function mapError(callable $mapper): Outcome
  {
    return Failure::fromThrowable($mapper($this->error));
  }

  /**
   * @param mixed|callable $value
   */
  public function getOrElse(mixed $value): mixed
  {
    return is_callable($value) ? $value($this->error) : $value;
  }

  public function flatMap(callable $mapper): mixed
  {
    return $this;
  }

  public function fold(?callable $onSuccess, ?callable $onFailure = null): mixed
  {
    $onFailure ??= fn(mixed $error) => $error instanceof Throwable ? throw $error : $error;

    return $onFailure($this->error);
  }

  private function __construct(Throwable $error)
  {
    $this->error = $error;
  }
}
