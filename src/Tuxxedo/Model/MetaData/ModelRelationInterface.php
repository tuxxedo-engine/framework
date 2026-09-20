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

use Tuxxedo\Model\Attribute\Relation\RelationAttributeInterface;

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

    public RelationAttributeInterface $attribute {
        get;
    }

    public ?string $typeColumn {
        get;
    }

    public ?string $idColumn {
        get;
    }

    /**
     * @var array<string, class-string>|null
     */
    public ?array $typeMap {
        get;
    }

    /**
     * @var non-empty-list<string>|null
     */
    public ?array $foreignKeyColumns {
        get;
    }

    /**
     * @var non-empty-list<string>|null
     */
    public ?array $referencedKeyColumns {
        get;
    }

    /**
     * @var non-empty-list<string>|null
     */
    public ?array $pivotSourceColumns {
        get;
    }

    /**
     * @var non-empty-list<string>|null
     */
    public ?array $pivotTargetColumns {
        get;
    }
}
