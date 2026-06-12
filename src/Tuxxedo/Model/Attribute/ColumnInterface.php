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
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;

interface ColumnInterface
{
    public ?string $name {
        get;
    }

    /**
     * @var class-string<CoercerInterface>|null
     */
    public ?string $coercer {
        get;
    }

    /**
     * @var class-string<BehaviorInterface>|null
     */
    public ?string $behavior {
        get;
    }

    public function getNativeType(
        DialectInterface $dialect,
    ): ?string;
}
