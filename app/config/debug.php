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

use Tuxxedo\Debug\Config\DebugConfig;
use Tuxxedo\Debug\Config\DebugConfigInterface;

return static fn (): DebugConfigInterface => new DebugConfig(
    alwaysShow: true,
    rootPath: \dirname(__DIR__, 2),
    registerPhpErrorHandler: true,
);
