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

namespace Fixture\Serializer;

class ClassicConstructor
{
    public string $name;
    public string $suffixed;

    public function __construct(
        string $name,
        string $suffix = '!',
    ) {
        $this->name = $name;
        $this->suffixed = $name . $suffix;
    }
}
