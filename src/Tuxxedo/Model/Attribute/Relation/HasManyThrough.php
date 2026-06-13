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
readonly class HasManyThrough implements RelationInterface
{
    public CascadeAction $onSave;
    public CascadeAction $onDelete;

    /**
     * @param class-string $related
     * @param class-string $through
     */
    public function __construct(
        public string $related,
        public string $through,
        public string $firstKey,
        public string $secondKey,
        public ?string $localKey = null,
        public ?string $secondLocalKey = null,
    ) {
        $this->onSave = CascadeAction::NO_ACTION;
        $this->onDelete = CascadeAction::NO_ACTION;
    }
}
