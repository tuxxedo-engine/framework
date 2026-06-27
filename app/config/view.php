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

use Tuxxedo\View\Lumi\Config\LumiConfig;
use Tuxxedo\View\Lumi\Config\LumiConfigInterface;

return static fn (): LumiConfigInterface => new LumiConfig(
    directory: __DIR__ . '/../views',
    cacheDirectory: __DIR__ . '/../views/cache',
    extension: '.lumi',
    alwaysCompile: true,
    disableErrorReporting: true,
);
