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

namespace App\Support;

use Tuxxedo\Router\PrefixDefaultsInterface;
use Tuxxedo\Router\StickyPrefixInterface;

class LocalePrefix implements PrefixDefaultsInterface, StickyPrefixInterface
{
    public private(set) string $path = '/{?language<language-code>}';

    public function getDefaultValue(string $argument): mixed
    {
        return null;
    }
}
