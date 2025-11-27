<?php

use Modoterra\Rune\Effect;
use Modoterra\Rune\Failure;
use Modoterra\Rune\Success;

describe('effect', function () {
  describe('fromThunk', function () {
    it('can be created from a thunk', function () {
      $effect = Effect::fromThunk(fn() => 42);
      expect($effect)->toBeInstanceOf(Effect::class);

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(42);
    });
  });

  describe('fromOutcome', function () {
    it('can be created from an outcome', function () {
      $success = Success::from(99);
      $effect = Effect::fromOutcome($success);
      expect($effect)->toBeInstanceOf(Effect::class);

      $outcome = $effect->run();
      expect($outcome)->toBe($success);
    });
  });

  describe('succeed', function () {
    it('can be created with succeed', function () {
      $effect = Effect::succeed(42);
      expect($effect)->toBeInstanceOf(Effect::class);

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(42);
    });
  });

  describe('fail', function () {
    it('can be created with fail', function () {
      $error = new Exception("Something went wrong");
      $effect = Effect::fail($error);
      expect($effect)->toBeInstanceOf(Effect::class);

      $outcome = $effect->run();
      expect($outcome->didFail())->toBeTrue();
      expect($outcome->error())->toBe($error);
    });
  });

  describe('run', function () {
    it('runs the underlying thunk', function () {
      $effect = Effect::fromThunk(fn() => 100);
      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(100);
    });

    it('lifts non-Outcome results into Success', function () {
      $effect = Effect::fromThunk(fn() => "Hello, World!");
      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe("Hello, World!");
    });
  });

  describe('map', function () {
    it('maps successful outcomes', function () {
      $effect = Effect::succeed(1);
      $mappedEffect = $effect->map(fn($value) => $value * 2);
      $outcome = $mappedEffect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(2);
    });

    it('maps with fromThunk', function () {
      $effect = Effect::fromThunk(fn() => 3);
      $mappedEffect = $effect->map(fn($value) => $value + 7);
      $outcome = $mappedEffect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(10);
    });
  });

  describe('flatMap', function () {
    it('flatMaps successful outcomes', function () {
      $effect = Effect::succeed(3);
      $flatMappedEffect = $effect->flatMap(fn($value) => Effect::succeed($value + 4));
      $outcome = $flatMappedEffect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(7);
    });
  });

  describe('fold', function () {
    it('folds successful outcomes', function () {
      $effect = Effect::succeed(5);
      $result = $effect->fold(
        onSuccess: fn($value) => "Success: $value"
      );
      expect($result)->toBe("Success: 5");
    });

    it('folds failed outcomes', function () {
      $failedEffect = Effect::fail(new Exception("Failure occurred"));
      $result = $failedEffect->fold(
        onFailure: fn($error) => "Failure: " . $error->getMessage()
      );
      expect($result)->toBe("Failure: Failure occurred");
    });
  });

  describe('memoize', function () {
    it('memoizes the outcome of the effect', function () {
      // We will pass this by reference so we can inspect it after running the effect.
      $counter = 0;

      $effect = Effect::fromThunk(function () use (&$counter) {
        return $counter++;
      })->memoize();

      // Before running, the counter is 0.
      expect($counter)->toBe(0);

      // This will set the counter to 1, but return 0.
      $first = $effect->run();
      expect($counter)->toBe(1);

      // It stays 1.
      $second = $effect->run();
      expect($counter)->toBe(1);

      // The outcome is correctly memoized, and the value is the same.
      // The thunk was only executed once, as indicated by the counter.
      // `$counter` is only post-incremented. So here it is 0.
      expect($first->value())->toBe(0);
      expect($second->value())->toBe(0);
    });
  });

  describe('tryCatch', function () {
    it('creates a successful effect when the thunk does not throw', function () {
      $effect = Effect::tryCatch(fn() => 10);
      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(10);
    });

    it('creates a failed effect when the thunk throws', function () {
      $effect = Effect::tryCatch(fn() => throw new Exception("Error occurred"));
      $outcome = $effect->run();
      expect($outcome->didFail())->toBeTrue();
      expect($outcome->error())->toBeInstanceOf(Exception::class);
      expect($outcome->error()->getMessage())->toBe("Error occurred");
    });
  });

  describe('mapError', function () {
    it('maps errors of failed effects', function () {
      $effect = Effect::fail(new Exception("Initial error"))
        ->mapError(fn($error) => new Exception("Mapped: " . $error->getMessage()));

      $outcome = $effect->run();
      expect($outcome->didFail())->toBeTrue();
      expect($outcome->error())->toBeInstanceOf(Exception::class);
      expect($outcome->error()->getMessage())->toBe("Mapped: Initial error");
    });
  });

  describe('recover', function () {
    it('recovers from a failed effect', function () {
      $effect = Effect::fail(new Exception("Initial failure"))
        ->recover(fn() => Effect::succeed(42));

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(42);
    });
  });

  describe('tap', function () {
    it('taps into successful effects without altering the value', function () {
      $tappedValues = [];
      $effect = Effect::succeed(7)
        ->tap(function ($value) use (&$tappedValues) {
          $tappedValues[] = $value;
        });

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toBe(7);
      expect($tappedValues)->toEqual([7]);
    });

    it('is not called on failure', function () {
      $tapped = false;
      $effect = Effect::fail(new Exception("Some error"))
        ->tap(function () use (&$tapped) {
          $tapped = true;
        });

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Failure::class);
      expect($outcome->didFail())->toBeTrue();
      expect($tapped)->toBeFalse();
    });
  });

  describe('tapError', function () {
    it('taps into failed effects without altering the error', function () {
      $tappedErrors = [];
      $effect = Effect::fail(new Exception("Initial error"))
        ->tapError(function ($error) use (&$tappedErrors) {
          $tappedErrors[] = $error;
        });

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Failure::class);
      expect($outcome->didFail())->toBeTrue();
      expect($outcome->error()->getMessage())->toBe("Initial error");
      expect($tappedErrors)->toHaveLength(1);
      expect($tappedErrors[0])->toBeInstanceOf(Exception::class);
      expect($tappedErrors[0]->getMessage())->toBe("Initial error");
    });

    it('is not called on success', function () {
      $tapped = false;
      $effect = Effect::succeed(10)
        ->tapError(function () use (&$tapped) {
          $tapped = true;
        });

      $outcome = $effect->run();
      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($tapped)->toBeFalse();
    });
  });

  describe('all', function () {
    it('combines multiple successful effects', function () {
      $effects = [
        Effect::succeed(1),
        Effect::succeed(2),
        Effect::succeed(3),
      ];

      $combinedEffect = Effect::all($effects);
      $outcome = $combinedEffect->run();

      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toEqual([1, 2, 3]);
    });

    it('returns the first failure among multiple effects', function () {
      $effects = [
        Effect::succeed(1),
        Effect::fail(new Exception("Second effect failed")),
        Effect::succeed(3),
      ];

      $combinedEffect = Effect::all($effects);
      $outcome = $combinedEffect->run();

      expect($outcome)->toBeInstanceOf(Failure::class);
      expect($outcome->didFail())->toBeTrue();
      expect($outcome->error()->getMessage())->toBe("Second effect failed");
    });

    it('preserves keys when combining effects', function () {
      $effects = [
        'first' => Effect::succeed(10),
        'second' => Effect::succeed(20),
        'third' => Effect::succeed(30),
      ];

      $combinedEffect = Effect::all($effects);
      $outcome = $combinedEffect->run();

      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toEqual([
        'first' => 10,
        'second' => 20,
        'third' => 30,
      ]);
    });

    it('handles an empty array of effects', function () {
      $combinedEffect = Effect::all([]);
      $outcome = $combinedEffect->run();

      expect($outcome)->toBeInstanceOf(Success::class);
      expect($outcome->didSucceed())->toBeTrue();
      expect($outcome->value())->toEqual([]);
    });
  });
});
