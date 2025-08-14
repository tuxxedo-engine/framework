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

namespace Tuxxedo\View\Lumi\Parser\Node;

enum AssignmentOperator: string
{
    case ADD = '+=';
    case SUBTRACT = '-=';
    case MULTIPLY = '*=';
    case DIVIDE = '/=';
    case MODULUS = '%=';
    case EXPONENTIATE = '**=';
    case BITWISE_AND = '&=';
    case BITWISE_OR = '|=';
    case BITWISE_XOR = '^=';
    case BITWISE_SHIFT_LEFT = '<<=';
    case BITWISE_SHIFT_RIGHT = '>>=';
}
