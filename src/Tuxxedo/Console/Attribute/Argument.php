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

namespace Tuxxedo\Console\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class Argument implements CommandParameterInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {
    }
}
