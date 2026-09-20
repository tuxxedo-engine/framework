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

namespace Tuxxedo\Model\Attribute\Aggregate;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class RelationAggregate
{
    public function __construct(
        public readonly string $relation,
        public readonly RelationAggregateFunction $function,
        public readonly ?string $column = null,
        public readonly ?string $alias = null,
    ) {
    }
}
