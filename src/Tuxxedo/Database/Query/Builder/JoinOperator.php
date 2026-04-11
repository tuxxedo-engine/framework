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

namespace Tuxxedo\Database\Query\Builder;

enum JoinOperator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL = '>=';
}
