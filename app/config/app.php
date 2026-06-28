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
use Tuxxedo\Application\Profile;
use Tuxxedo\Env\EnvInterface;

return static fn (EnvInterface $env): AppConfigInterface => new AppConfig(
    name: $env->string('APP_NAME'),
    version: $env->string('APP_VERSION'),
    profile: $env->enum('APP_PROFILE', Profile::class),
    url: $env->string('APP_URL'),
);
