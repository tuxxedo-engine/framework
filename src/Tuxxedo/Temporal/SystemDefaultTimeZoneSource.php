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

class SystemDefaultTimeZoneSource implements TimeZoneSourceInterface
{
    public SourcePriority $priority {
        get {
            return SourcePriority::LOWEST;
        }
    }

    public function __construct(
        private readonly TimeZoneInterface $default,
    ) {
    }

    public function detect(): TimeZoneInterface
    {
        return $this->default;
    }
}
