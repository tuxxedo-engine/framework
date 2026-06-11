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

// @todo Add ?string $coercer arg (class-string<CoercerInterface>|null) to the contract. Concrete attributes default to their associated coercer class (JsonCoercer for #[Json], DateTimeCoercer for #[DateTime], etc.) or null where coercion doesn't apply (#[Varchar], #[Integer], etc.). coercer: null disables coercion entirely.
interface ColumnInterface
{
    public ?string $name {
        get;
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): ?string;
}
