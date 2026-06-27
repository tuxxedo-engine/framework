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

namespace Tuxxedo\Application\Config;

use Tuxxedo\Application\Profile;
use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('app')]
readonly class AppConfig implements AppConfigInterface
{
    public function __construct(
        public string $name,
        public string $version,
        public Profile $profile,
        public string $url,
    ) {
    }
}
