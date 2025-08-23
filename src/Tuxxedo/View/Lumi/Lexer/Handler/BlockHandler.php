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

namespace Tuxxedo\View\Lumi\Lexer\Handler;

use Tuxxedo\View\Lumi\ByteStreamInterface;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Token\AssignToken;
use Tuxxedo\View\Lumi\Token\BreakToken;
use Tuxxedo\View\Lumi\Token\ContinueToken;
use Tuxxedo\View\Lumi\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Token\ElseToken;
use Tuxxedo\View\Lumi\Token\EndToken;
use Tuxxedo\View\Lumi\Token\ForToken;
use Tuxxedo\View\Lumi\Token\IfToken;
use Tuxxedo\View\Lumi\Token\TextToken;
use Tuxxedo\View\Lumi\Token\TokenInterface;
use Tuxxedo\View\Lumi\Token\WhileToken;

class BlockHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{%';
    }

    public function getEndingSequence(): string
    {
        return '%}';
    }

    public function tokenize(
        ByteStreamInterface $stream,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        $buffer = '';

        while (!$stream->eof()) {
            if ($stream->match($this->getEndingSequence())) {
                $stream->consumeSequence($this->getEndingSequence());

                return $this->parseBlock(
                    expression: \mb_trim($buffer),
                    expressionLexer: $expressionLexer,
                );
            }

            $buffer .= $stream->consume();
        }

        return [
            new TextToken($this->getStartingSequence() . $buffer),
        ];
    }

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    private function parseBlock(
        string $expression,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        if (\mb_strpos($expression, ' ') !== false) {
            [$directive, $expr] = \explode(' ', $expression, 2);
            $directive = \mb_strtolower($directive);

            return match ($directive) {
                'if' => [
                    new IfToken(),
                    ...$expressionLexer->parse($expr),
                    new EndToken(),
                ],
                'elseif' => [
                    new ElseIfToken(),
                    ...$expressionLexer->parse($expr),
                    new EndToken(),
                ],
                'for' => $this->parseFor($expr, $expressionLexer),
                'foreach' => $this->parseForeach($expr, $expressionLexer),
                'while' => [
                    new WhileToken(),
                    ...$expressionLexer->parse($expr),
                    new EndToken(),
                ],
                'set' => [
                    new AssignToken(),
                    ...$expressionLexer->parse($expr),
                    new EndToken(),
                ],
                'break' => [
                    new BreakToken($this->parseLoopDepth($expr)),
                ],
                'continue' => [
                    new ContinueToken($this->parseLoopDepth($expr)),
                ],
                default => throw LexerException::fromSequenceNotFound(
                    sequence: $directive,
                ),
            };
        }

        $directive = \mb_strtolower($expression);

        return [
            match ($directive) {
                'else' => new ElseToken(),
                'endif', 'endfor', 'endwhile', 'endforeach' => new EndToken(),
                default => throw LexerException::fromSequenceNotFound(
                    sequence: $directive,
                ),
            },
        ];
    }

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    private function parseFor(
        string $expression,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        if (\preg_match('/^\s*(\w+)(?:\s*,\s*(\w+))?\s+in\s+(.+)$/ui', $expression, $matches) !== 1) {
            throw LexerException::fromInvalidForSyntax();
        }

        $value = $matches[1];
        $key = $matches[2] !== '' ? $matches[2] : null;
        $expr = $matches[3];

        return [
            new ForToken($value, $key),
            ...$expressionLexer->parse($expr),
            new EndToken(),
        ];
    }

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    private function parseForeach(
        string $expression,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        if (\preg_match('/^(.+?)\s+as\s+(\w+)(?:\s*=>\s*(\w+))?$/ui', $expression, $matches) !== 1) {
            throw LexerException::fromInvalidForeachSyntax();
        }

        $expr = $matches[1];
        $key = $matches[2];
        $value = isset($matches[3]) ? $matches[3] : null;

        if ($value === null) {
            $value = $key;
            $key = null;
        }

        return [
            new ForToken($value, $key),
            ...$expressionLexer->parse($expr),
            new EndToken(),
        ];
    }

    /**
     * @return numeric-string|null
     *
     * @throws LexerException
     */
    private function parseLoopDepth(string $expr): ?string
    {
        $expr = \mb_trim($expr);

        if ($expr === '') {
            return null;
        }

        if (\preg_match('/^[1-9][0-9]*$/u', $expr) !== 1) {
            throw LexerException::fromInvalidLoopDepth();
        }

        return (string) (int) $expr;
    }
}
