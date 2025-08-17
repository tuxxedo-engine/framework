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
    public static function fromUnknownToken(
        string $tokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s"',
                $tokenName,
            ),
        );
    }

    public static function fromStateLevelMismatch(): self
    {
        return new self(
            message: 'State mismatch, the nesting level cannot go below 0',
        );
    }
}
