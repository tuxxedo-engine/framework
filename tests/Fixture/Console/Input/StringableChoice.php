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

namespace Fixture\Console\Input;

class StringableChoice implements \Stringable
{
    public function __construct(
        public readonly string $label,
    ) {
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
