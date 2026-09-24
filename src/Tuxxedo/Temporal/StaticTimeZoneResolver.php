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

namespace Tuxxedo\Temporal;

class StaticTimeZoneResolver implements TimeZoneResolverInterface
{
    public function __construct(
        private readonly TimeZoneInterface $timeZone,
    ) {
    }

    public function currentTimeZone(): TimeZoneInterface
    {
        return $this->timeZone;
    }
}
