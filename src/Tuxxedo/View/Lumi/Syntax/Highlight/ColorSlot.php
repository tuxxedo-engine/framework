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

namespace Tuxxedo\View\Lumi\Syntax\Highlight;

enum ColorSlot
{
    case TEXT;
    case COMMENT;
    case DELIMITER;
    case KEYWORD;
    case OPERATOR;
    case STRING;
    case NUMBER;
    case BOOL;
    case NULL;
    case IDENTIFIER;
    case FUNCTION_NAME;
    case MEMBER_NAME;
    case FILTER_NAME;
    case PIPE;
    case CONCAT;
    case NULL_COALESCE;
    case KEY;
}
