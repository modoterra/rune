<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true
    ])
    ->setIndent("  ")
    ->setLineEnding("\n")
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
    )
;
