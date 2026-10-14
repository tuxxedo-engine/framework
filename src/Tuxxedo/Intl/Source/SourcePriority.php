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

namespace Tuxxedo\Intl\Source;

enum SourcePriority: int
{
    case HIGHEST = -100;
    case HIGH = -50;
    case NORMAL = 0;
    case LOW = 50;
    case LOWEST = 100;
}
