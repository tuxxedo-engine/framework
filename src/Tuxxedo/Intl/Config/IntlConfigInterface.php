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

namespace Tuxxedo\Intl\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\MissingKeyMode;

#[DefaultImplementation(class: IntlConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface IntlConfigInterface
{
    public ?string $rootDirectory {
        get;
    }

    public LocaleInterface $defaultLocale {
        get;
    }

    /**
     * @var list<LocaleInterface>
     */
    public array $supportedLocales {
        get;
    }

    public MissingKeyMode $missingKeyMode {
        get;
    }
}
