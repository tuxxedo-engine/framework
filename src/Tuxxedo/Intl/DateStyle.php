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

namespace Tuxxedo\Intl;

enum DateStyle: string
{
    case NONE = 'none';
    case SHORT = 'short';
    case MEDIUM = 'medium';
    case LONG = 'long';
    case FULL = 'full';
}
