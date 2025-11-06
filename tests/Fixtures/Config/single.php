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

use Fixtures\Config\TestEnum;

return [
    'types' => [
        'string' => 'foo',
        'int' => 42,
        'float' => 13.37,
        'bool' => false,
        'null' => null,
    ],
    'foo' => [
        'bar' => [
            'baz' => 'Hello World',
        ],
    ],
    'a' => 'b',
    'enum' => [
        'foo' => TestEnum::FOO,
        'bar' => TestEnum::BAR,
        'baz' => TestEnum::BAZ,
    ],
];
