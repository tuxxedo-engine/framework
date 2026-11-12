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

use Tuxxedo\Http\Method;

interface HttpRequestInfoInterface
{
    public Method $method {
        get;
    }

    public string $path {
        get;
    }

    public string $host {
        get;
    }

    public string $clientIp {
        get;
    }

    public ?string $route {
        get;
    }
}
