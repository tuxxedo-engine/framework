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

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Router\PrefixDefaultsInterface;

class LocaleAwarePrefix implements PrefixDefaultsInterface
{
    public private(set) string $uri = '/{?language<language-code>}';

    public function __construct(
        private readonly RequestInterface $request,
    ) {
    }

    public function getDefaultValue(string $argument): mixed
    {
        return match ($argument) {
            'language' => \strtolower(\substr($this->request->headers->preferredLanguage ?? 'en', 0, 2)),
            default => null,
        };
    }
}
