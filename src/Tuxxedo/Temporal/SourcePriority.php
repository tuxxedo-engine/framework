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

enum SourcePriority: int
{
    case HIGHEST = -2;

    case HIGH = -1;

    case NORMAL = 0;

    case LOW = 1;

    case LOWEST = 2;
}
