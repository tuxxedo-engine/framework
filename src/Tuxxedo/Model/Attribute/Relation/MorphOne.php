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

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class MorphOne implements InversePolymorphicRelationInterface
{
    /**
     * @param class-string $related
     * @param string|non-empty-array<string> $idColumn
     * @param string|non-empty-array<string>|null $localKey
     * @param array<string, class-string>|null $typeMap
     */
    public function __construct(
        public string $related,
        public string $typeColumn,
        public string|array $idColumn,
        public string|array|null $localKey = null,
        public ?array $typeMap = null,
        public CascadeAction $onSave = CascadeAction::NO_ACTION,
        public CascadeAction $onDelete = CascadeAction::NO_ACTION,
    ) {
    }
}
