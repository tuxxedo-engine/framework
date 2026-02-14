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

interface UrlInterface
{
    public string $base {
        get;
    }

    public function get(
        string $path,
    ): string;

    public function toRfc3986(
        string $path = '',
    ): Rfc3986\Uri;

    public function toWhatWg(
        string $path = '',
    ): WhatWg\Url;
}
