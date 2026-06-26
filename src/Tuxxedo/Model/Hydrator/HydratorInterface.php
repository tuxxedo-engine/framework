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

namespace Tuxxedo\Model\Hydrator;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Database\Hydrator\HydratorInterface as DatabaseHydratorInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

#[DefaultImplementation(class: Hydrator::class, lifecycle: Lifecycle::SINGLETON)]
interface HydratorInterface extends DatabaseHydratorInterface
{
    /**
     * @param object[] $parents
     * @param array<string, ?\Closure(Relation<object>): Relation<object>> $with
     *
     * @throws ModelException
     */
    public function eagerLoad(
        array $parents,
        array $with,
    ): void;
}
