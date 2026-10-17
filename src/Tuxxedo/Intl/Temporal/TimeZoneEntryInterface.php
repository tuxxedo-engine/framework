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

namespace Tuxxedo\Intl\Temporal;

interface TimeZoneEntryInterface
{
    public string $name {
        get;
    }

    public string $displayName {
        get;
    }

    public string $city {
        get;
    }

    public string $offset {
        get;
    }

    public string $region {
        get;
    }

    public ?string $metazone {
        get;
    }
}
