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
use Tuxxedo\Http\Kernel\Kernel;

class ApplicationFactory
{
    final private function __construct()
    {
    }

    public static function createFromDirectory(
        string $directory,
        bool $loadServices = true,
    ): Kernel {
        $config = Config::createFromDirectory($directory . '/config');

        $kernel = new Kernel(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
            config: $config,
        );

        if ($loadServices) {
            $kernel->serviceProvider(
                new FileServiceProvider($directory . '/services.php'),
            );
        }

        return $kernel;
    }
}
