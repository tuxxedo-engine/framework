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
readonly class MorphTo implements PolymorphicRelationInterface
{
    /**
     * @param array<string, class-string>|null $typeMap
     */
    public function __construct(
        public string $typeColumn,
        public string $idColumn,
        public ?array $typeMap = null,
        public CascadeAction $onSave = CascadeAction::NO_ACTION,
        public CascadeAction $onDelete = CascadeAction::NO_ACTION,
    ) {
    }
}
