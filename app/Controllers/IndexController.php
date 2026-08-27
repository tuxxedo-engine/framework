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

namespace App\Controllers;

use Tuxxedo\Application\Config\AppConfigInterface;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Version;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

readonly class IndexController
{
    public function __construct(
        private AppConfigInterface $appConfig,
    ) {
    }

    #[Route\Get(path: '/', name: 'index')]
    public function hello(): ViewInterface
    {
        return new View(
            name: 'index',
            scope: [
                'engineVersion' => Version::SIMPLE,
                'engineVersionFull' => Version::FULL,
                'phpVersion' => \PHP_VERSION,
                'phpSapi' => \PHP_SAPI,
                'appName' => $this->appConfig->name,
                'appVersion' => $this->appConfig->version,
                'appProfile' => $this->appConfig->profile->name,
                'appUrl' => $this->appConfig->url,
                'timezone' => \date_default_timezone_get(),
            ],
        );
    }
}
