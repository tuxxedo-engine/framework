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

interface ColumnInterface
{
    public ?string $name {
        get;
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): ?string;
}
