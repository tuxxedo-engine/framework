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

namespace Tuxxedo\Model\MetaData;

use Tuxxedo\Model\Attribute\Aggregate\RelationAggregateFunction;

readonly class ModelRelationAggregate implements ModelRelationAggregateInterface
{
    /**
     * @param 'int'|'float' $slotType
     */
    public function __construct(
        public string $property,
        public string $alias,
        public string $relation,
        public RelationAggregateFunction $function,
        public ?string $column,
        public string $slotType,
    ) {
    }
}
