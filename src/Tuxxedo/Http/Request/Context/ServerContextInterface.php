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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\Method;

// @todo expects? e.g. does the client expects json?
// @todo IP address functionality
// @todo Prefers language functionality
interface ServerContextInterface
{
    public bool $https {
        get;
    }

    public bool $ajax {
        get;
    }

    public Method $method {
        get;
    }

    public string $uri {
        get;
    }

    public string $fullUri {
        get;
    }

    public string $queryString {
        get;
    }

    public string $userAgent {
        get;
    }

    public string $host {
        get;
    }

    public int $port {
        get;
    }
}
