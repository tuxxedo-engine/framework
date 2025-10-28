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

namespace Tuxxedo\View\Lumi\Syntax\Token;

enum BuiltinTokenNames
{
    case ASSIGN;
    case BLOCK;
    case BREAK;
    case CHARACTER;
    case COMMENT;
    case CONTINUE;
    case DECLARE;
    case DO;
    case ECHO;
    case ELSEIF;
    case ELSE;
    case END;
    case ENDBLOCK;
    case ENDFOR;
    case ENDFOREACH;
    case ENDIF;
    case ENDWHILE;
    case FOR;
    case FOREACH;
    case IDENTIFIER;
    case IF;
    case LAYOUT;
    case LITERAL;
    case OPERATOR;
    case TEXT;
    case WHILE;
}
