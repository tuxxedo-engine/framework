<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in(
        [
            'app/',
            'public/',
            'src/',
            'tests/',
        ],
    )
    ->exclude(
        [
            'views/cache/',
        ],
    );


return (new Config())
    ->setParallelConfig(
        ParallelConfigFactory::detect(),
    )
    ->setRules(
        [
            '@PSR12' => true,
            'strict_param' => true,
            'single_space_around_construct' => true,
            'fully_qualified_strict_types' => true,
            'no_unused_imports' => true,
            'array_syntax' => [
                'syntax' => 'short',
            ],
            'trailing_comma_in_multiline' => [
                'elements' => [
                    'arrays',
                    'arguments',
                ],
            ],
            'native_function_invocation' => [
                'include' => [
                    '@all',
                ],
                'strict' => true,
            ],
            'ordered_imports' => [
                'sort_algorithm' => 'alpha',
                'imports_order' => [
                    'class',
                    'function',
                    'const',
                ],
                'case_sensitive' => true,
            ],
        ],
    )
    ->setFinder($finder);
