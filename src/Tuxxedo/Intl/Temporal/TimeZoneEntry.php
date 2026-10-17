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

class TimeZoneEntry implements TimeZoneEntryInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $displayName,
        public readonly string $city,
        public readonly string $offset,
        public readonly string $region,
        public readonly ?string $metazone,
    ) {
    }
}
