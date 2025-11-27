<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use Throwable;

abstract class Outcome
{
  protected ?Throwable $error = null;
  protected mixed $value = null;

  abstract public function didSucceed(): bool;

  abstract public function didFail(): bool;

  abstract public function map(callable $mapper): Outcome;

  abstract public function mapError(callable $mapper): Outcome;

  abstract public function flatMap(callable $mapper): mixed;

  abstract public function getOrElse(mixed $value): mixed;

  abstract public function fold(?callable $onSuccess, ?callable $onFailure): mixed;

  public function value(): mixed
  {
    return $this->value;
  }

  public function error(): ?Throwable
  {
    return $this->error;
  }
}
