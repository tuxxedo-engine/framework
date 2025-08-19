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

namespace Tuxxedo\View\Lumi\Parser;

class ParserException extends \Exception
{
    public static function fromUnexpectedToken(
        string $tokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s"',
                $tokenName,
            ),
        );
    }

    public static function fromMalformedToken(): self
    {
        return new self(
            message: 'Internal token is malformed',
        );
    }

    public static function fromUnexpectedTokenWithExpects(
        string $tokenName,
        string $expectedTokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s", expected "%s"',
                $tokenName,
                $expectedTokenName,
            ),
        );
    }
}
