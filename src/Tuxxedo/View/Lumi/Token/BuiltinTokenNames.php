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

namespace Tuxxedo\View\Lumi\Token;

enum BuiltinTokenNames
{
    case ASSIGN;
    case BREAK;
    case CHARACTER;
    case CONTINUE;
    case DO;
    case ECHO;
    case ELSEIF;
    case ELSE;
    case END;
    case FOR;
    case IDENTIFIER;
    case IF;
    case LITERAL;
    case OPERATOR;
    case TEXT;
    case WHILE;
}
