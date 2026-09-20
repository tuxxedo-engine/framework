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

interface ModelRelationAggregateInterface
{
    public string $property {
        get;
    }

    public string $alias {
        get;
    }

    public string $relation {
        get;
    }

    public RelationAggregateFunction $function {
        get;
    }

    public ?string $column {
        get;
    }

    /**
     * @var 'int'|'float'
     */
    public string $slotType {
        get;
    }
}
