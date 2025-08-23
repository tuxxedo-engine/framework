<?php

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
            'native_function_invocation' => [
                'include' => [
                    '@all',
                ],
                'strict' => true,
            ],
        ],
    )
    ->setFinder($finder);
