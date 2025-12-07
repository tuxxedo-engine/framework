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

namespace Tuxxedo\Http;

readonly class Header implements HeaderInterface
{
    final public function __construct(
        public string $name,
        public string $value,
    ) {
    }

    public function withValue(
        string $value,
    ): static {
        return clone (
            $this,
            [
                'value' => $value,
            ],
        );
    }
}
