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

interface TimeZoneGroupInterface
{
    public string $name {
        get;
    }

    public string $displayName {
        get;
    }

    public string $offset {
        get;
    }

    /**
     * @var list<string>
     */
    public array $cities {
        get;
    }

    /**
     * @var list<TimeZoneEntryInterface>
     */
    public array $zones {
        get;
    }
}
