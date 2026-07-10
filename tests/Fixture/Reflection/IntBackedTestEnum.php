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

namespace Fixture\Reflection;

enum IntBackedTestEnum: int
{
    case POSITIVE = 1;
    case ZERO = 0;
    case NEGATIVE = -1;
}
