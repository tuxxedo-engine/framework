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

namespace Tuxxedo\Intl\Source;

use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Locale;

class DefaultLocaleSource implements LocaleSourceInterface
{
    public function __construct(
        private readonly IntlConfigInterface $config,
    ) {
    }

    public function detect(): ?Locale
    {
        return $this->config->defaultLocale;
    }
}
