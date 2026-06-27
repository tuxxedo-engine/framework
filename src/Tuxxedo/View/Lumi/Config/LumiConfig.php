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

namespace Tuxxedo\View\Lumi\Config;

use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('view')]
readonly class LumiConfig implements LumiConfigInterface
{
    public function __construct(
        public string $directory = '',
        public string $cacheDirectory = '',
        public string $extension = '',
        public bool $alwaysCompile = false,
        public bool $disableErrorReporting = true,
    ) {
    }
}
