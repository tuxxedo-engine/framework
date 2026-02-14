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

namespace Tuxxedo\Http\Url;

use Uri\Rfc3986;
use Uri\WhatWg;

class Url implements UrlInterface
{
    public function __construct(
        public readonly string $base,
    ) {
    }

    public function get(
        string $path,
    ): string {
        return $this->base . $path;
    }

    public function toRfc3986(
        string $path = '',
    ): Rfc3986\Uri {
        return new Rfc3986\Uri(
            uri: $this->get($path),
        );
    }

    public function toWhatWg(
        string $path = '',
    ): WhatWg\Url {
        return new WhatWg\Url(
            uri: $this->get($path),
        );
    }
}
