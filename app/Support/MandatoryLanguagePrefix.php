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

use Tuxxedo\Router\PrefixInterface;

class MandatoryLanguagePrefix implements PrefixInterface
{
    public private(set) string $uri = '/{language<language-code>}';
    public private(set) array $arguments = [
        'language',
    ];
}
