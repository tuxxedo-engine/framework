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

interface BindingInterface
{
    public string|int $name {
        get;
    }

    public string|int|float|bool|null $value {
        get;
    }

    public ?ParameterType $type {
        get;
    }
}
