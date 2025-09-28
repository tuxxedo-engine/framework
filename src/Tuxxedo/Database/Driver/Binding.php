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

namespace Tuxxedo\Database\Driver;

readonly class Binding implements BindingInterface
{
    public function __construct(
        public string|int|float|bool|null $value,
        public ?ParameterType $type = null,
        public string|int $name = '',
    ) {
    }
}
