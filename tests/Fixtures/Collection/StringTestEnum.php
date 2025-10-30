<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Fixtures\Collection;

enum StringTestEnum: string
{
    case DK = 'Denmark';
    case FI = 'Finland';
    case IS = 'Iceland';
    case NO = 'Norway';
    case SE = 'Sweden';
}
