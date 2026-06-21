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

use Tuxxedo\Model\Relation;

/**
 * @internal
 */
final class EagerLoadNode
{
    /**
     * @var array<string, self>
     */
    public array $children = [];

    /**
     * @var ?\Closure(Relation<object>): Relation<object>
     */
    public ?\Closure $constraint = null;
}
