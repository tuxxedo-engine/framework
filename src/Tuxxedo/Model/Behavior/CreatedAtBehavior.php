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

namespace Tuxxedo\Model\Behavior;

use Tuxxedo\Model\MetaData\ModelColumnInterface;
use Tuxxedo\Reflection\PropertyReflector;
use Tuxxedo\Temporal\ClockInterface;

class CreatedAtBehavior implements BeforeInsertBehaviorInterface
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function beforeInsert(
        object $model,
        ModelColumnInterface $column,
    ): void {
        PropertyReflector::createFromObject($model, $column->property)
            ->setValue($model, $this->clock->now());
    }
}
