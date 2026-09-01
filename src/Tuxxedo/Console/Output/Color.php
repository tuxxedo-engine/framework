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

namespace Tuxxedo\Console\Output;

enum Color: int
{
    case BLACK = 30;
    case RED = 31;
    case GREEN = 32;
    case YELLOW = 33;
    case BLUE = 34;
    case MAGENTA = 35;
    case CYAN = 36;
    case WHITE = 37;
    case DEFAULT = 39;
    case LIGHT_BLACK = 90;
    case LIGHT_RED = 91;
    case LIGHT_GREEN = 92;
    case LIGHT_YELLOW = 93;
    case LIGHT_BLUE = 94;
    case LIGHT_MAGENTA = 95;
    case LIGHT_CYAN = 96;
    case LIGHT_WHITE = 97;

    public function foreground(): int
    {
        return $this->value;
    }

    public function background(): int
    {
        return $this->value + 10;
    }
}
