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

namespace Tuxxedo\Console\Config;

class SuggestionPolicy
{
    public function __construct(
        public readonly bool $enabled,
        public readonly int $maxDistance,
    ) {
    }

    public static function default(): self
    {
        return new self(
            enabled: true,
            maxDistance: 3,
        );
    }

    public static function disabled(): self
    {
        return new self(
            enabled: false,
            maxDistance: 0,
        );
    }
}
