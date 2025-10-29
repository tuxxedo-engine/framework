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

namespace App\Controllers;

use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Version;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

readonly class IndexController
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    #[Route\Get(uri: '/')]
    public function hello(): ViewInterface
    {
        return new View(
            name: 'index',
            scope: [
                'engineVersion' => Version::SIMPLE,
                'phpVersion' => \PHP_VERSION,
                'appName' => $this->kernel->appName,
                'appVersion' => $this->kernel->appVersion,
            ],
        );
    }
}
