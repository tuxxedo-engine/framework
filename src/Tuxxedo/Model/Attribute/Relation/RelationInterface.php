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

namespace Tuxxedo\Model\Attribute\Relation;

use Tuxxedo\Model\CascadeAction;

// @todo HasOneThrough / HasManyThrough relations — reach a far model through an intermediate one (e.g., User → Owner through Car). New attribute classes implementing RelationInterface, plus hydration via the intermediate, cascade semantics consistent with HasOne / HasMany
interface RelationInterface
{
    /**
     * @var class-string
     */
    public string $related {
        get;
    }

    public CascadeAction $onSave {
        get;
    }

    public CascadeAction $onDelete {
        get;
    }
}
