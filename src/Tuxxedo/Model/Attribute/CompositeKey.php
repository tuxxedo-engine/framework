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

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
readonly class CompositeKey
{
    /**
     * @var non-empty-array<string>
     */
    public array $columns;

    /**
     * @param non-empty-array<string> ...$columns
     */
    public function __construct(
        string ...$columns,
    ) {
        /** @var non-empty-array<string> $columns */
        $this->columns = $columns;
    }
}
