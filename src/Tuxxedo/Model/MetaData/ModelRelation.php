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

readonly class ModelRelation implements ModelRelationInterface
{
    /**
     * @param class-string $relatedClass
     */
    public function __construct(
        public string $property,
        public string $relatedClass,
        public bool $nullable,
        public RelationInterface $attribute,
    ) {
    }
}
