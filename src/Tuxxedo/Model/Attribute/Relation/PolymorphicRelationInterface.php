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

interface PolymorphicRelationInterface extends RelationAttributeInterface
{
    public string $typeColumn {
        get;
    }

    public string $idColumn {
        get;
    }

    /**
     * @var array<string, class-string>|null
     */
    public ?array $typeMap {
        get;
    }
}
