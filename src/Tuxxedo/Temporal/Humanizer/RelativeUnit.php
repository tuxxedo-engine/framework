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

namespace Tuxxedo\Temporal\Humanizer;

enum RelativeUnit
{
    case SECONDS;

    case MINUTES;

    case HOURS;

    case DAYS;

    case WEEKS;

    case MONTHS;

    case YEARS;
}
