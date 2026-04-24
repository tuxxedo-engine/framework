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
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\PrimaryKey;

// @todo Should the attributes be translated into DTOs? So we don't need TKey for $columns but can always set
//       ->columns[0]->name to the value if its NULL? This is how the Router and others does it
// @todo $primaryKey and $compositeKey is odd, because it means that both can potentially exist, this is not
//       true as only either of them or NULL can, so wrapping them is probably the best way
readonly class ModelMetaData implements ModelMetaDataInterface
{
    /**
     * @param class-string $model
     * @param non-empty-array<string, ColumnInterface> $columns
     */
    public function __construct(
        public string $model,
        public string $table,
        public array $columns,
        public ?PrimaryKey $primaryKey = null,
        public ?CompositeKey $compositeKey = null,
    ) {
    }
}
