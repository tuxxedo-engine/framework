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

enum BinaryOperator
{
    case ADD;
    case SUBTRACT;
    case MULTIPLY;
    case DIVIDE;
    case EQUAL;
    case NOT_EQUAL;
    case GREATER;
    case LESS;
    case GREATER_EQUAL;
    case LESS_EQUAL;
    case AND;
    case OR;
    case SHIFT_LEFT;
    case SHIFT_RIGHT;
}
