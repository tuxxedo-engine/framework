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

enum CldrCategory: string
{
    case ZERO = 'zero';
    case ONE = 'one';
    case TWO = 'two';
    case FEW = 'few';
    case MANY = 'many';
    case OTHER = 'other';
}
