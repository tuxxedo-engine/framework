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

use Tuxxedo\Model\Attribute\Relation\MorphTo;

readonly class MorphToRelationMetaData implements MorphToRelationMetaDataInterface
{
    /**
     * @param non-empty-list<string> $idColumns
     * @param array<string, class-string>|null $typeMap
     */
    public function __construct(
        public string $property,
        public bool $nullable,
        public MorphTo $attribute,
        public string $typeColumn,
        public string $idColumn,
        public array $idColumns,
        public ?array $typeMap = null,
    ) {
    }
}
