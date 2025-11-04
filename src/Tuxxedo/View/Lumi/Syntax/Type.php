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

use Tuxxedo\View\Lumi\Parser\ParserException;

enum Type
{
    case STRING;
    case INT;
    case FLOAT;
    case BOOL;
    case NULL;

    public static function fromString(
        string $name,
    ): ?self {
        return match ($name) {
            self::STRING->name => self::STRING,
            self::INT->name => self::INT,
            self::FLOAT->name => self::FLOAT,
            self::BOOL->name => self::BOOL,
            self::NULL->name => self::NULL,
            default => null,
        };
    }
}
