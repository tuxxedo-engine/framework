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

use Tuxxedo\Database\Query\Dialect\DialectInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class Column implements ColumnInterface
{
    public function __construct(
        public ?string $name = null,
    ) {
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): null {
        return null;
    }
}
