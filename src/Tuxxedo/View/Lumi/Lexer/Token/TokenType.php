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

enum TokenType
{
    case TEXT;
    case IF;
    case ELSEIF;
    case ELSE;
    case ENDIF;
    case ECHO;
    case FOR;
    case ENDFOR;
    case WHILE;
    case ENDWHILE;
    case SET;
}
