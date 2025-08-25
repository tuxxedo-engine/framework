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

namespace Tuxxedo\View\Lumi\Lexer\Expression;

use Tuxxedo\View\Lumi\ByteStream;
use Tuxxedo\View\Lumi\ByteStreamInterface;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Syntax\AssignmentOperator;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Syntax\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\UnaryOperator;
use Tuxxedo\View\Lumi\Token\BuiltinTypeNames;
use Tuxxedo\View\Lumi\Token\CharacterToken;
use Tuxxedo\View\Lumi\Token\OperatorToken;
use Tuxxedo\View\Lumi\Token\TokenInterface;
use Tuxxedo\View\Lumi\Token\LiteralToken;
use Tuxxedo\View\Lumi\Token\IdentifierToken;

class ExpressionLexer implements ExpressionLexerInterface
{
    /**
     * @var string[]
     */
    private readonly array $operators;

    /**
     * @var string[]
     */
    private readonly array $characterSymbols;

    public function __construct()
    {
        $operators = [];
        $characterSymbols = [];

        foreach ([...AssignmentOperator::cases(), ...BinaryOperator::cases(), ...UnaryOperator::cases()] as $operator) {
            $operators[] = $operator->symbol();
        }

        foreach ([...CharacterSymbol::cases()] as $characterSymbol) {
            $characterSymbols[] = $characterSymbol->symbol();
        }

        $this->operators = $operators;
        $this->characterSymbols = $characterSymbols;
    }

    public function parse(
        int $startingLine,
        string $operand,
    ): array {
        $tokens = [];
        $buffer = '';
        $line = $startingLine;
        $inQuote = false;
        $quoteChar = '';
        $stream = ByteStream::createFromString($operand);

        while (!$stream->eof()) {
            $char = $stream->peek(1);

            if ($inQuote) {
                $buffer .= $stream->consume();

                if ($char === $quoteChar) {
                    $slashes = 0;

                    for ($i = \mb_strlen($buffer) - 2; $i >= 0; $i--) {
                        if ($buffer[$i] === '\\') {
                            $slashes++;
                        } else {
                            break;
                        }
                    }

                    if ($slashes % 2 === 0) {
                        $inQuote = false;
                        $tokens[] = new LiteralToken(
                            line: $line,
                            op1: \mb_substr($buffer, 1, -1),
                            op2: BuiltinTypeNames::STRING->name,
                        );

                        $buffer = '';
                        $line += $stream->line;
                    }
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                if ($buffer !== '') {
                    $tokens[] = $this->classifyToken($line, $buffer);
                    $buffer = '';
                    $line += $stream->line;
                }

                $inQuote = true;
                $quoteChar = $char;
                $buffer .= $stream->consume();

                continue;
            }

            if (\preg_match('/^\s$/u', $char) === 1) {
                $stream->consumeWhitespace();

                if ($buffer !== '') {
                    $tokens[] = $this->classifyToken($line, $buffer);
                    $buffer = '';
                    $line += $stream->line;
                }

                continue;
            }

            if ($this->isStartOfNumber($char, $stream)) {
                $buffer .= $stream->consume();

                while (!$stream->eof()) {
                    $next = $stream->peek(1);

                    if (\preg_match('/^[0-9eE.+-]$/', $next) === 1) {
                        $buffer .= $stream->consume();
                    } else {
                        break;
                    }
                }

                if ($this->isValidInteger($buffer)) {
                    $tokens[] = new LiteralToken(
                        line: $line,
                        op1: $buffer,
                        op2: BuiltinTypeNames::INT->name,
                    );
                } elseif ($this->isValidFloat($buffer)) {
                    $tokens[] = new LiteralToken(
                        line: $line,
                        op1: $buffer,
                        op2: BuiltinTypeNames::FLOAT->name,
                    );
                } else {
                    throw LexerException::fromInvalidNumber(
                        value: $buffer,
                    );
                }

                $buffer = '';
                $line += $stream->line;

                continue;
            }

            if (\preg_match('/^\p{L}|\p{N}|_$/u', $char) === 1) {
                $buffer .= $stream->consume();

                continue;
            }

            if ($buffer !== '') {
                $tokens[] = $this->classifyToken($line, $buffer);
                $buffer = '';
                $line += $stream->line;
            }

            if (\preg_match('/^[^\p{L}\p{N}\s]$/u', $char) === 1) {
                $buffer = '';
                $line += $stream->line;
                $lastValid = null;

                while (!$stream->eof()) {
                    $char = $stream->peek(1);

                    if (\preg_match('/^[^\p{L}\p{N}\s]$/u', $char) !== 1) {
                        break;
                    }

                    $buffer .= $stream->consume();

                    if (
                        \in_array($buffer, $this->operators, true) ||
                        \in_array($buffer, $this->characterSymbols, true)
                    ) {
                        $lastValid = $buffer;
                    }

                    $next = $stream->peek(1);
                    $isNextSymbol = \preg_match('/^[^\p{L}\p{N}\s]$/u', $next) === 1;

                    if (!$isNextSymbol || !$this->isSymbolPrefix($buffer . $next)) {
                        break;
                    }
                }

                if ($lastValid !== null) {
                    $tokens[] = $this->classifySymbol($line, $lastValid);

                    $remainingLength = \mb_strlen($buffer) - \mb_strlen($lastValid);
                    for ($i = 0; $i < $remainingLength; $i++) {
                        $stream->consume();
                    }

                    $buffer = '';
                    $line += $stream->line;
                } else {
                    throw LexerException::fromUnknownSymbol(
                        symbol: $buffer,
                    );
                }
            }
        }

        if ($buffer !== '') {
            $tokens[] = $this->classifyToken($line, $buffer);
        }

        if ($inQuote) {
            throw LexerException::fromInvalidQuotedString(
                quoteChar: $quoteChar,
            );
        }

        if (\sizeof($tokens) === 0) {
            throw LexerException::fromEmptyExpression();
        }

        return $tokens;
    }

    private function classifyToken(
        int $line,
        string $value,
    ): TokenInterface {
        if ($this->isValidFloat($value)) {
            return new LiteralToken(
                line: $line,
                op1: $value,
                op2: BuiltinTypeNames::FLOAT->name,
            );
        }

        if ($this->isValidInteger($value)) {
            return new LiteralToken(
                line: $line,
                op1: $value,
                op2: BuiltinTypeNames::INT->name,
            );
        }

        if (\in_array(\mb_strtolower($value), ['true', 'false'], true)) {
            return new LiteralToken(
                line: $line,
                op1: $value,
                op2: BuiltinTypeNames::BOOL->name,
            );
        }

        if (\mb_strtolower($value) === 'null') {
            return new LiteralToken(
                line: $line,
                op1: $value,
                op2: BuiltinTypeNames::NULL->name,
            );
        }

        return new IdentifierToken(
            line: $line,
            op1: $value,
        );
    }

    private function classifySymbol(
        int $line,
        string $symbol,
    ): TokenInterface {
        if (\in_array($symbol, $this->operators, true)) {
            return new OperatorToken(
                line: $line,
                op1: $symbol,
            );
        }

        if (\in_array($symbol, $this->characterSymbols, true)) {
            return new CharacterToken(
                line: $line,
                op1: $symbol,
            );
        }

        throw LexerException::fromUnknownSymbol(
            symbol: $symbol,
        );
    }

    private function isStartOfNumber(
        string $char,
        ByteStreamInterface $stream,
    ): bool {
        $next = $stream->peek(2)[1] ?? '';

        if ($char === '-' && (\preg_match('/^\d$/', $next) === 1 || $next === '.')) {
            return true;
        }

        if ($char === '.' && \preg_match('/^\d$/', $next) === 1) {
            return true;
        }

        return \preg_match('/^\d$/', $char) === 1;
    }

    private function isValidInteger(string $value): bool
    {
        return \preg_match('/^-?\d+$/', $value) === 1;
    }

    private function isValidFloat(string $value): bool
    {
        return \preg_match('/^-?(?:\d*\.\d+|\d+\.\d*)(?:[eE]-?\d+)?$/', $value) === 1;
    }

    private function isSymbolPrefix(string $prefix): bool
    {
        foreach ($this->operators as $op) {
            if (\str_starts_with($op, $prefix)) {
                return true;
            }
        }

        foreach ($this->characterSymbols as $sym) {
            if (\str_starts_with($sym, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
