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

namespace Tuxxedo\Application\Config;

use Tuxxedo\Application\Profile;

interface AppConfigInterface
{
    public string $name {
        get;
    }

    public string $version {
        get;
    }

    public Profile $profile {
        get;
    }

    public string $url {
        get;
    }
}
