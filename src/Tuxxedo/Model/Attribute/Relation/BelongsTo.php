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

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class BelongsTo implements RelationInterface
{
    /**
     * @param class-string $related
     */
    public function __construct(
        public string $related,
        public string $foreignKey,
        public ?string $key = null,
    ) {
    }
}
