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

readonly class UnnamespacedConfig implements UnnamespacedConfigInterface
{
    public function __construct(
        public string $value = '',
    ) {
    }
}
