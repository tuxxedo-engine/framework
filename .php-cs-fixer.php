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
            'app/views/cache',
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
            'single_space_around_construct' => false,
            'array_syntax' => [
                'syntax' => 'short',
            ],
        ],
    )
    ->setFinder($finder);
