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

enum NativeType
{
    case STRING;
    case INT;
    case FLOAT;
    case BOOL;
    case NULL;

    /**
     * @throws ParserException
     */
    public static function fromString(
        string $name,
        int $line,
    ): self {
        return match ($name) {
            'STRING' => self::STRING,
            'INT' => self::INT,
            'FLOAT' => self::FLOAT,
            'BOOL' => self::BOOL,
            'NULL' => self::NULL,
            default => throw ParserException::fromUnexpectedToken(
                tokenName: $name,
                line: $line,
            ),
        };
    }

    public static function fromValueNativeType(
        string|int|float|bool|null $value,
    ): self {
        return match (true) {
            \is_string($value) => self::STRING,
            \is_int($value) => self::INT,
            \is_float($value) => self::FLOAT,
            \is_bool($value) => self::BOOL,
            \is_null($value) => self::NULL,
        };
    }

    public function cast(
        string $value,
    ): string|int|float|bool|null {
        return match ($this) {
            self::STRING => $value,
            self::INT => \intval($value),
            self::FLOAT => \floatval($value),
            self::BOOL => $value === 'true',
            self::NULL => null,
        };
    }
}
