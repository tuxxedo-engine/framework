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

namespace Tuxxedo\View\Lumi\Syntax\Operator;

enum Precedence: int
{
    case ACCESS = 80;
    case EXPONENTIATION = 70;
    case TIGHT = 60;
    case ADDITIVE = 50;
    case SHIFT = 45;
    case BITWISE_AND = 40;
    case BITWISE_XOR = 35;
    case BITWISE_OR = 30;
    case COMPARISON = 25;
    case EQUALITY = 20;
    case LOGICAL_AND = 15;
    case LOGICAL_XOR = 14;
    case LOGICAL_OR = 13;
    case NULL_COALESCE = 12;
    case LOWEST = 0;
}
