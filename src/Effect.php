<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use Closure;
use Throwable;

class Effect
{
  private Closure $thunk;

  public static function fromThunk(callable $thunk): Effect
  {
    return new Effect($thunk);
  }

  public static function fromOutcome(Outcome $outcome): Effect
  {
    return new Effect(fn(): Outcome => $outcome);
  }

  public static function succeed(mixed $value): Effect
  {
    return new Effect(fn(): Outcome => Success::from($value));
  }

  public static function fail(Throwable $error): Effect
  {
    return new Effect(fn(): Outcome => Failure::fromThrowable($error));
  }

  public static function tryCatch(callable $thunk): Effect
  {
    return new Effect(function () use ($thunk) {
      try {
        return Success::from($thunk());
      } catch (Throwable $error) {
        return Failure::fromThrowable($error);
      }
    });
  }

  public static function all(array $effects): Effect
  {
    return new Effect(function () use ($effects) {
      $results = [];
      foreach ($effects as $key => $effect) {
        $outcome = $effect->run();
        if ($outcome->didFail()) {
          return $outcome;
        }
        $results[$key] = $outcome->value();
      }
      return Success::from($results);
    });
  }

  /**
   * Call the underlying thunk and lift the result into an Outcome if necessary.
   */
  public function run(): Outcome
  {
    $result = ($this->thunk)();
    if ($result instanceof Outcome) return $result;
    return Success::from($result);
  }

  public function memoize(): Effect
  {
    return new MemoizedEffect(fn() => $this->run());
  }

  public function map(callable $mapper): Effect
  {
    return new Effect(fn() => $this->run()->map($mapper));
  }

  public function mapError(callable $mapper): Effect
  {
    return new Effect(fn() => $this->run()->mapError($mapper));
  }

  public function flatMap(callable $mapper): Effect
  {
    return new Effect(fn() => $this->run()->flatMap($mapper));
  }

  public function tap(callable $tap): Effect
  {
    return $this->map(function (mixed $value) use ($tap) {
      $tap($value);
      return $value;
    });
  }

  public function tapError(callable $tap): Effect
  {
    return $this->mapError(function (Throwable $error) use ($tap) {
      $tap($error);
      return $error;
    });
  }

  /**
   * @template T
   * @param callable(\Throwable): Effect<T> $recovery
   * @return Effect<T>
   */
  public function recover(callable $recovery): Effect
  {
    return new Effect(fn() => $this->run()->fold(
      onSuccess: fn(mixed $value) => Success::from($value),
      onFailure: fn(mixed $error) => $recovery($error)->run()
    ));
  }

  public function fold(?callable $onSuccess = null, ?callable $onFailure = null): mixed
  {
    return $this->run()->fold($onSuccess, $onFailure);
  }

  protected function __construct(callable $thunk)
  {
    $this->thunk = $thunk;
  }
}
