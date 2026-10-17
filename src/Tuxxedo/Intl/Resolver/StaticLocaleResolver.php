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

namespace Tuxxedo\Intl\Resolver;

use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\LocaleInterface;

class StaticLocaleResolver implements LocaleResolverInterface
{
    public function __construct(
        private readonly IntlConfigInterface $config,
    ) {
    }

    public function currentLocale(): LocaleInterface
    {
        return $this->config->defaultLocale;
    }
}
