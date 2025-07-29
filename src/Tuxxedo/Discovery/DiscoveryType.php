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

use Tuxxedo\Application\ExtensionInterface;
use Tuxxedo\Application\ServiceProviderInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;

enum DiscoveryType
{
    case EXTENSIONS;
    case MIDDLEWARE;
    case SERVICES;

    public function isValidSubClass(string $className): bool
    {
        return match ($this) {
            self::EXTENSIONS => \is_a($className, ExtensionInterface::class, true),
            self::MIDDLEWARE => \is_a($className, MiddlewareInterface::class, true),
            self::SERVICES => \is_a($className, ServiceProviderInterface::class, true),
        };
    }
}
