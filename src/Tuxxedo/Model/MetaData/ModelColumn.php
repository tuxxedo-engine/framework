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

use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Attribute\Relation\RelationInterface;

readonly class ModelColumn implements ModelColumnInterface
{
    /**
     * @param RelationInterface[] $relations
     */
    public function __construct(
        public string $name,
        public bool $nullable,
        public ColumnInterface $attribute,
        public ?ModelPrimaryKeyInterface $primaryKey = null,
        public ?ModelIdentifierInterface $identifier = null,
        public array $relations = [],
    ) {
    }
}
