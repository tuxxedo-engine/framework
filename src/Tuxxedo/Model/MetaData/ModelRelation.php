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

readonly class ModelRelation implements ModelRelationInterface
{
    /**
     * @param class-string $relatedClass
     * @param array<string, class-string>|null $typeMap
     * @param non-empty-list<string>|null $foreignKeyColumns
     * @param non-empty-list<string>|null $referencedKeyColumns
     * @param non-empty-list<string>|null $pivotSourceColumns
     * @param non-empty-list<string>|null $pivotTargetColumns
     */
    public function __construct(
        public string $property,
        public string $relatedClass,
        public bool $nullable,
        public RelationAttributeInterface $attribute,
        public ?string $typeColumn = null,
        public ?string $idColumn = null,
        public ?array $typeMap = null,
        public ?array $foreignKeyColumns = null,
        public ?array $referencedKeyColumns = null,
        public ?array $pivotSourceColumns = null,
        public ?array $pivotTargetColumns = null,
    ) {
    }
}
