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

use Tuxxedo\View\Lumi\ByteStream;
use Tuxxedo\View\Lumi\ByteStreamInterface;
use Tuxxedo\View\Lumi\Syntax\AssignmentOperator;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Syntax\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\UnaryOperator;
use Tuxxedo\View\Lumi\Token\BuiltinTypeNames;
use Tuxxedo\View\Lumi\Token\TypeToken;
use Tuxxedo\View\Lumi\Token\VariableToken;
use Tuxxedo\View\Lumi\Token\OperatorToken;
use Tuxxedo\View\Lumi\Token\CharacterToken;
use Tuxxedo\View\Lumi\Token\TokenInterface;

// @todo Check filter support
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

    public function parse(string $operand): array
    {
        $tokens = [];
        $buffer = '';
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
                        $tokens[] = new TypeToken(
                            op1: $buffer,
                            op2: BuiltinTypeNames::STRING->name,
                        );
                        $buffer = '';
                    }
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                if ($buffer !== '') {
                    $tokens[] = $this->classifyToken($buffer);
                    $buffer = '';
                }

                $inQuote = true;
                $quoteChar = $char;
                $buffer .= $stream->consume();

                continue;
            }

            if (\preg_match('/^\s$/u', $char) === 1) {
                $stream->consumeWhitespace();

                if ($buffer !== '') {
                    $tokens[] = $this->classifyToken($buffer);
                    $buffer = '';
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

                if ($this->isValidNumber($buffer)) {
                    $tokens[] = new TypeToken(
                        op1: $buffer,
                        op2: BuiltinTypeNames::FLOAT->name,
                    );
                } else {
                    throw LexerException::fromInvalidNumber(
                        value: $buffer,
                    );
                }

                $buffer = '';

                continue;
            }

            if (\preg_match('/^\p{L}|\p{N}$/u', $char) === 1) {
                $buffer .= $stream->consume();

                continue;
            }

            if ($buffer !== '') {
                $tokens[] = $this->classifyToken($buffer);
                $buffer = '';
            }

            $symbol = $stream->consume();
            $tokens[] = $this->classifySymbol($symbol);
        }

        if ($buffer !== '') {
            $tokens[] = $this->classifyToken($buffer);
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

    private function classifyToken(string $value): TokenInterface
    {
        if (\preg_match('/^[+-]?(\d*\.\d+|\d+\.\d*)([eE][+-]?\d+)?$/', $value) === 1) {
            return new TypeToken(
                op1: $value,
                op2: BuiltinTypeNames::FLOAT->name,
            );
        }

        if (\is_numeric($value)) {
            return new TypeToken(
                op1: $value,
                op2: BuiltinTypeNames::INT->name,
            );
        }

        if (\in_array(\mb_strtolower($value), ['true', 'false'], true)) {
            return new TypeToken(
                op1: $value,
                op2: BuiltinTypeNames::BOOL->name,
            );
        }

        if (\mb_strtolower($value) === 'null') {
            return new TypeToken(
                op1: $value,
                op2: BuiltinTypeNames::NULL->name,
            );
        }

        return new VariableToken(
            op1: $value,
        );
    }

    private function classifySymbol(string $char): TokenInterface
    {
        if (\in_array($char, $this->operators, true)) {
            return new OperatorToken($char);
        }

        if (\in_array($char, $this->characterSymbols, true)) {
            return new CharacterToken($char);
        }

        throw LexerException::fromUnknownSymbol(
            symbol: $char,
        );
    }

    private function isStartOfNumber(
        string $char,
        ByteStreamInterface $stream,
    ): bool {
        if ($char === '.' && \is_numeric($stream->peek(2)[1] ?? '')) {
            return true;
        }

        return \is_numeric($char) || ($char === '-' && \is_numeric($stream->peek(2)[1] ?? ''));
    }

    private function isValidNumber(string $value): bool
    {
        return \preg_match('/^[+-]?(\d*\.\d+|\d+\.\d*|\d+)([eE][+-]?\d+)?$/', $value) === 1;
    }
}
