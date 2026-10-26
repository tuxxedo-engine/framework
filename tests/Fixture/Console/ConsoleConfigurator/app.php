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

use Tuxxedo\Application\Config\AppConfig;
use Tuxxedo\Application\Config\AppConfigInterface;
use Tuxxedo\Application\Environment;

return static fn (): AppConfigInterface => new AppConfig(
    name: 'TuxxedoConsoleApp',
    version: '2.3.4',
    environment: Environment::STAGING,
    url: 'https://example.test/',
);
