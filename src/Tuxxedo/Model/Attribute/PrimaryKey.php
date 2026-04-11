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

namespace Tuxxedo\Model\Attribute;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class PrimaryKey
{
    public function __construct(
        public string $column,
        public bool $autoIncrement = true,
    ) {
    }
}
