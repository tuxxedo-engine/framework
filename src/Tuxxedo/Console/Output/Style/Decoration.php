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

namespace Tuxxedo\Console\Output\Style;

enum Decoration: int
{
    case BOLD = 1;
    case DIM = 2;
    case ITALIC = 3;
    case UNDERLINE = 4;
}
