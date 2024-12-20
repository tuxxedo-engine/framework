<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2024 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Application;

use Tuxxedo\Application\ApplicationState;
use Tuxxedo\Config\Config;

class ApplicationFactory
{
    private function __construct()
    {
    }

    public static function createFromDirectory(string $directory): Application
    {
        $config = Config::createFromDirectory($directory . '/config');

        return new Application(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appState: $config->getEnum('app.state', ApplicationState::class),
            config: $config,
        );
    }
}
