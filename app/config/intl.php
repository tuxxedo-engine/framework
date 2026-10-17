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

use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Locale;

return static fn (EnvInterface $env): IntlConfigInterface => new IntlConfig(
    defaultLocale: Locale::parse($env->string('APP_LOCALE', 'en-US')),
    rootDirectory: \dirname(__DIR__) . '/language',
    supportedLocales: [
        Locale::parse('en-US'),
        Locale::parse('cs-CZ'),
    ],
);
