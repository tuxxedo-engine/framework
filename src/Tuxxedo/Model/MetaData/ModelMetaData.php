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

use Tuxxedo\Model\Attribute\Identifier;

readonly class ModelMetaData implements ModelMetaDataInterface
{
    /**
     * @param class-string $model
     * @param non-empty-array<ModelColumnInterface> $columns
     * @param Identifier[] $identifiers
     */
    public function __construct(
        public string $model,
        public string $table,
        public ModelPrimaryKeyInterface|ModelCompositeKeyInterface|null $key,
        public array $columns,
        public array $identifiers,
    ) {
    }
}
