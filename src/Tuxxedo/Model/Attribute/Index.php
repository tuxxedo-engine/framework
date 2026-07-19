<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Model\Attribute;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
readonly class Index
{
    /**
     * @var list<string>
     */
    public array $columns;

    public function __construct(
        string ...$columns,
    ) {
        $this->columns = \array_values($columns);
    }
}
