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

namespace Fixture\Config\Typed;

use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('outer.inner.deep')]
readonly class NestedConfig implements NestedConfigInterface
{
    public function __construct(
        public string $label = '',
    ) {
    }
}
