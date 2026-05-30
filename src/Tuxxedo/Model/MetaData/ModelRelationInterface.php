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

use Tuxxedo\Model\Attribute\Relation\RelationInterface;

interface ModelRelationInterface
{
    public string $property {
        get;
    }

    /**
     * @var class-string
     */
    public string $relatedClass {
        get;
    }

    public bool $nullable {
        get;
    }

    public RelationInterface $attribute {
        get;
    }
}
