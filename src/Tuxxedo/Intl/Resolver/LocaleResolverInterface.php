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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Intl\LocaleInterface;

#[DefaultImplementation(class: StaticLocaleResolver::class, lifecycle: Lifecycle::SINGLETON)]
interface LocaleResolverInterface
{
    public function currentLocale(): LocaleInterface;
}
