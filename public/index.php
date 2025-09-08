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

use Tuxxedo\Application\ApplicationConfigurator;

require_once __DIR__ . '/../vendor/autoload.php';

ApplicationConfigurator::createDefaultFromDirectory(__DIR__ . '/../app')->run();
