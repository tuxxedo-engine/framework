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

namespace Tuxxedo\View\Lumi\Node;

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\BuiltinTypeNames;

enum NodeNativeType
{
    case STRING;
    case INT;
    case FLOAT;
    case BOOL;
    case NULL;

    /**
     * @throws ParserException
     */
    public static function fromTokenNativeType(
        string $tokenNativeType,
    ): self {
        return match ($tokenNativeType) {
            BuiltinTypeNames::STRING->name => self::STRING,
            BuiltinTypeNames::INT->name => self::INT,
            BuiltinTypeNames::FLOAT->name => self::FLOAT,
            BuiltinTypeNames::BOOL->name => self::BOOL,
            BuiltinTypeNames::NULL->name => self::NULL,
            default => throw ParserException::fromUnexpectedTokenNativeType(
                tokenNativeType: $tokenNativeType,
                expectedTokenNativeTypes: \array_map(
                    static fn (BuiltinTypeNames $type): string => $type->name,
                    BuiltinTypeNames::cases(),
                ),
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
}
