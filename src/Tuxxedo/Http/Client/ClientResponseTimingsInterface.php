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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Temporal\DurationInterface;

interface ClientResponseTimingsInterface
{
    public DurationInterface $dns {
        get;
    }

    public DurationInterface $connect {
        get;
    }

    public DurationInterface $tls {
        get;
    }

    public DurationInterface $waiting {
        get;
    }

    public DurationInterface $download {
        get;
    }

    public DurationInterface $total {
        get;
    }
}
