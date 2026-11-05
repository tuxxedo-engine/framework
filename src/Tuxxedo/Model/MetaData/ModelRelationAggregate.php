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

use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;

readonly class ModelRelationAggregate implements ModelRelationAggregateInterface
{
    /**
     * @param 'int'|'float' $slotType
     * @param array<string, bool> $callbackParameters
     */
    public function __construct(
        public string $property,
        public string $alias,
        public string $relation,
        public AggregateFunction $function,
        public ?string $column,
        public string $slotType,
        public ?\Closure $callback = null,
        public array $callbackParameters = [],
        public bool $callbackVariadic = false,
    ) {
    }
}
