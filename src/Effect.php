<?php

declare(strict_types=1);

namespace Modoterra\Rune;

use Throwable;

/**
 * @template-covariant V
 */
class Effect
{
  /**
   * @var callable(): Outcome<V>
   */
  private $thunk;

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
   * @template T
   * @param Outcome<T> $outcome
   * @return self<T>
   */
  public static function fromOutcome(Outcome $outcome): self
  {
    return new self(fn () => $outcome);
  }

  /**
   * @template T
   * @param T $value
   * @return self<T>
   */
  public static function succeed(mixed $value): self
  {
    return self::fromThunk(fn () => Success::from($value));
  }

  /**
   * @param Throwable $error
   * @return self<Failure>
   */
  public static function fail(Throwable $error): self
  {
    return self::fromThunk(fn () => Failure::fromThrowable($error));
  }

  /**
   * @template T
   * @param callable(): Outcome<T> $thunk
   * @return self<mixed>
   */
  public static function tryCatch(callable $thunk): self
  {
    return self::fromThunk(function () use ($thunk) {
      try {
        return $thunk();
      } catch (Throwable $error) {
        return Failure::fromThrowable($error);
      }
    });
  }

  /**
   * @template T
   * @param array<self<T>> $effects
   * @return self<array<T>>
   */
  public static function all(array $effects): self
  {
    return self::fromThunk(
      function () use ($effects) {
        $results = [];

        foreach ($effects as $key => $effect) {
          $outcome = $effect->run();
          if ($outcome->didFail()) {
            /** @var Failure */
            return $outcome;
          }
          $results[$key] = $outcome->value();
        }

        return Success::from($results);
      }
    );
  }

  /**
   * @return Outcome<V>
   */
  public function run(): Outcome
  {
    return ($this->thunk)();
  }

  /**
   * @return MemoizedEffect<mixed>
   */
  public function memoize(): MemoizedEffect
  {
    return MemoizedEffect::fromThunk($this->thunk);
  }

  /**
   * @template T
   * @param callable(Outcome<V>): Outcome<T> $mapper
   * @return self<T>
   */
  public function map(callable $mapper): self
  {
    return self::fromThunk(fn () => $this->run()->map($mapper));
  }

  /**
   * @param callable(Throwable): Throwable $mapper
   * @return self<V>
   */
  public function mapError(callable $mapper): self
  {
    return self::fromThunk(fn () => $this->run()->mapError($mapper));
  }

  /**
   * @template T
   * @param callable(V): T $mapper
   * @return self<T>
   */
  public function flatMap(callable $mapper): self
  {
    return self::fromThunk(fn () => Success::from($this->run()->flatMap($mapper)));
  }

  /**
   * @param callable(Outcome<V>): void $tap
   * @return self<V>
   */
  public function tap(callable $tap): self
  {
    return $this->map(
      function ($value) use ($tap) {
        $tap($value);
        return $value;
      }
    );
  }

  /**
   * @param callable(Throwable): void $tap
   * @return self<V>
   */
  public function tapError(callable $tap): self
  {
    return $this->mapError(function (Throwable $error) use ($tap) {
      $tap($error);
      return $error;
    });
  }

  /**
   * @template T
   * @param callable(Throwable): self<T> $rescuer
   * @return self<V|T>
   */
  public function recover(callable $rescuer): self
  {
    return self::fromThunk(
      function () use ($rescuer) {
        $outcome = $this->run();
        if ($outcome->didSucceed()) {
          return $outcome;
        }

        /** @var Throwable */
        $error = $outcome->error();
        return $rescuer($error)->run();
      }
    );
  }

  /**
   * @template T
   * @param ?callable(V): T $onSuccess
   * @param ?callable(Throwable): T $onFailure
   * @return mixed
   */
  public function fold(?callable $onSuccess = null, ?callable $onFailure = null): mixed
  {
    return $this->run()->fold($onSuccess, $onFailure);
  }

  /**
   * @param callable(): Outcome<V> $thunk
   */
  protected function __construct(callable $thunk)
  {
    $this->thunk = $thunk;
  }
}
