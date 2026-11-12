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

namespace Tuxxedo\Logger\Processor\Info;

use Tuxxedo\Application\Environment;

readonly class AppInfo implements AppInfoInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly Environment $environment,
    ) {
    }
}
