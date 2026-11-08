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

namespace Fixture\Serializer;

use Tuxxedo\Serializer\Attribute\DateFormat;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalTime;

class Schedule
{
    public function __construct(
        public readonly Instant $at,
        public readonly LocalDate $day,
        public readonly LocalTime $opensAt,
        #[DateFormat('H:i')]
        public readonly LocalTime $closesAt,
        #[DateFormat('d.m.Y')]
        public readonly LocalDate $published,
        #[DateFormat('Y-m-d H:i')]
        public readonly InstantInterface $createdAt,
    ) {
    }
}
