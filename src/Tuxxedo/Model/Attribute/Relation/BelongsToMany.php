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
readonly class BelongsToMany implements RelationInterface
{
    /**
     * @param class-string $related
     * @param string|non-empty-array<string> $localKey
     * @param string|non-empty-array<string> $foreignKey
     */
    public function __construct(
        public string $related,
        public string $table,
        public string|array $localKey,
        public string|array $foreignKey,
        public CascadeAction $onSave = CascadeAction::NO_ACTION,
        public CascadeAction $onDelete = CascadeAction::NO_ACTION,
    ) {
    }
}
