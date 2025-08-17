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

namespace Tuxxedo\View\Lumi\Syntax;

enum UnaryOperator: string implements OperatorAssociativityInterface
{
    case NOT = '!';
    case NEGATE = '-';
    case BITWISE_NOT = '~';
    // @todo INCREMENT_PRE (right) ++
    // @todo INCREMENT_POST (left) ++
    // @todo DECREMENT_PRE (right) --
    // @todo DECREMENT_POST (left) --

    public function associativity(): OperatorAssociativity
    {
        return OperatorAssociativity::RIGHT;
    }
}
