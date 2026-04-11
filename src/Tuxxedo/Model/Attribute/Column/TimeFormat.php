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

namespace Tuxxedo\Model\Attribute\Column;

enum TimeFormat: string
{
    case DEFAULT = 'H:i:s';
    case DEFAULT_WITH_TIMEZONE = 'H:i:sP';
    case TWELVE = 'h:i:s A';
    case UNIX = 'U';
}
