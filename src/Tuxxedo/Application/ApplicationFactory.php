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

namespace Tuxxedo\Application;

use Tuxxedo\Config\Config;

// @todo Re-consider the factory approach
class ApplicationFactory
{
    final private function __construct()
    {
    }

    public static function createFromDirectory(
        string $directory,
    ): Application {
        $config = Config::createFromDirectory($directory . '/config');

        return new Application(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', ApplicationProfile::class),
            config: $config,
        );
    }
}
