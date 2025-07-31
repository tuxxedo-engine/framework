<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Router;

readonly class ArgumentNode
{
    public function __construct(
        public string $label,
        public ArgumentKind $kind,
        public ?string $constraint = null,
    ) {
    }
}
