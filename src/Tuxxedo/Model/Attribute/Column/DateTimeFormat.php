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

enum DateTimeFormat: string
{
    case DEFAULT = 'Y-m-d H:i:s';
    case ISO_8601 = 'Y-m-d\TH:i:sP';
    case UNIX = 'U';
}
