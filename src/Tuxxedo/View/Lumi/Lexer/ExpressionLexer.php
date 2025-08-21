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

namespace Tuxxedo\View\Lumi\Lexer;

use Tuxxedo\View\Lumi\Token\VariableToken;

class ExpressionLexer implements ExpressionLexerInterface
{
    public function parse(
        string $operand,
    ): array {
        $operand = \trim($operand);

        if ($operand === '') {
            throw LexerException::fromEmptyExpression();
        }

        if (!$this->isValidIdentifier($operand)) {
            throw LexerException::fromInvalidIdentifier(
                expression: $operand,
            );
        }

        return [
            new VariableToken($operand),
        ];
    }

    private function isValidIdentifier(string $value): bool
    {
        return \preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $value) === 1;
    }
}
