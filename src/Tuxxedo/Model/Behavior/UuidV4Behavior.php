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
use Tuxxedo\Uuid\UuidV4;

class UuidV4Behavior implements BeforeInsertBehaviorInterface
{
    public function beforeInsert(
        object $model,
        ModelColumnInterface $column,
    ): void {
        $reflector = PropertyReflector::createFromObject($model, $column->property);

        if ($reflector->getValue($model) !== null) {
            return;
        }

        $reflector->setValue($model, UuidV4::generate()->value);
    }
}
