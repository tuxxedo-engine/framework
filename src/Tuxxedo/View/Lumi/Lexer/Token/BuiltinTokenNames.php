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

namespace Tuxxedo\View\Lumi\Lexer\Token;

enum BuiltinTokenNames
{
    case ASSIGN;
    case BREAK;
    case CONTINUE;
    case ECHO;
    case ELSEIF;
    case ELSE;
    case END;
    case FOR;
    case IF;
    case TEXT;
    case VARIABLE;
    case WHILE;
}
