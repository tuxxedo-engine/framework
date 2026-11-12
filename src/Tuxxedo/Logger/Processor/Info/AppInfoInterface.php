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

interface AppInfoInterface
{
    public string $name {
        get;
    }

    public string $version {
        get;
    }

    public Environment $environment {
        get;
    }
}
