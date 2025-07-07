<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Discovery;

use Tuxxedo\Http\Kernel\ExtensionInterface;
use Tuxxedo\Http\Kernel\ServiceProviderInterface;

enum DiscoveryType
{
    case EXTENSIONS;
    case SERVICES;

    public function isValidSubClass(string $className): bool
    {
        return match ($this) {
            self::EXTENSIONS => \is_a($className, ExtensionInterface::class, true),
            self::SERVICES => \is_a($className, ServiceProviderInterface::class, true),
        };
    }
}
