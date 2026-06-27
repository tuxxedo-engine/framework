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

namespace Tuxxedo\Session\Config;

use Tuxxedo\Config\Attribute\ConfigNamespace;
use Tuxxedo\Http\SameSite;

#[ConfigNamespace('session')]
readonly class SessionConfig implements SessionConfigInterface
{
    public function __construct(
        public int $lifetime = 3600,
        public string $path = '/',
        public string $domain = '',
        public bool $httpOnly = true,
        public bool $secure = false,
        public SameSite $sameSite = SameSite::STRICT,
    ) {
    }
}
