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

namespace Support\Model;

use Tuxxedo\Model\Behavior\BeforeInsertBehaviorInterface;
use Tuxxedo\Model\Behavior\BeforeUpdateBehaviorInterface;
use Tuxxedo\Model\MetaData\ModelColumnInterface;
use Tuxxedo\Reflection\PropertyReflector;

class SentinelBehavior implements BeforeInsertBehaviorInterface, BeforeUpdateBehaviorInterface
{
    public function beforeInsert(
        object $model,
        ModelColumnInterface $column,
    ): void {
        PropertyReflector::createFromObject($model, $column->property)
            ->setValue($model, 'inserted');
    }

    public function beforeUpdate(
        object $model,
        ModelColumnInterface $column,
    ): void {
        PropertyReflector::createFromObject($model, $column->property)
            ->setValue($model, 'updated');
    }
}
