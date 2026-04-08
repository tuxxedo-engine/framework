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

namespace Tuxxedo\Http\Url;

class Url implements UrlInterface
{
    public function __construct(
        public readonly string $base,
    ) {
    }

    public function get(
        string $path,
    ): string {
        return $this->base . \ltrim($path, '/');
    }
}
