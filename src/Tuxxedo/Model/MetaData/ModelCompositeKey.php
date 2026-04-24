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

class ModelCompositeKey implements ModelCompositeKeyInterface
{
    /**
     * @param non-empty-array<string> $columns
     */
    public function __construct(
        public array $columns,
    ) {
    }
}
