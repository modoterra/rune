<?php

namespace Modoterra\Rune;

class Success extends Outcome
{
  public static function from(mixed $value): Success
  {
    return new Success($value);
  }

  private function __construct(mixed $value)
  {
    $this->value = $value;
  }

  public function didSucceed(): bool
  {
    return true;
  }

  public function didFail(): bool
  {
    return false;
  }

  public function map(callable $mapper): Outcome
  {
    return Success::from($mapper($this->value));
  }

  public function mapError(callable $_): Outcome
  {
    return $this;
  }

  public function flatMap(callable $mapper): Outcome
  {
    return $mapper($this->value)->run();
  }

  public function getOrElse(mixed $_): mixed
  {
    return $this->value;
  }

  public function fold(?callable $onSuccess = null, ?callable $onFailure = null): mixed
  {
    $onSuccess ??= fn(mixed $value) => $value;

    return $onSuccess($this->value);
  }
}
