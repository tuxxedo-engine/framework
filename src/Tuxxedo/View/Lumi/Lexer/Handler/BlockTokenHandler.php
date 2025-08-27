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
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\ContinueToken;
use Tuxxedo\View\Lumi\Token\DeclareToken;
use Tuxxedo\View\Lumi\Token\DoToken;
use Tuxxedo\View\Lumi\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Token\ElseToken;
use Tuxxedo\View\Lumi\Token\EndForeachToken;
use Tuxxedo\View\Lumi\Token\EndForToken;
use Tuxxedo\View\Lumi\Token\EndIfToken;
use Tuxxedo\View\Lumi\Token\EndToken;
use Tuxxedo\View\Lumi\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Token\ForeachToken;
use Tuxxedo\View\Lumi\Token\ForToken;
use Tuxxedo\View\Lumi\Token\IfToken;
use Tuxxedo\View\Lumi\Token\TextToken;
use Tuxxedo\View\Lumi\Token\TokenInterface;
use Tuxxedo\View\Lumi\Token\WhileToken;

class BlockTokenHandler implements TokenHandlerInterface
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
        $line = $stream->line;

        while (!$stream->eof()) {
            if ($stream->match($this->getEndingSequence())) {
                $stream->consumeSequence($this->getEndingSequence());

                return $this->parseBlock(
                    startingLine: $stream->line,
                    expression: \mb_trim($buffer),
                    expressionLexer: $expressionLexer,
                );
            }

            $buffer .= $stream->consume();
        }

        return [
            new TextToken(
                line: $line,
                op1: $this->getStartingSequence() . $buffer,
            ),
        ];
    }

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    private function parseBlock(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        if (\mb_strpos($expression, ' ') !== false) {
            [$directive, $expr] = \explode(' ', $expression, 2);
            $directive = \mb_strtolower($directive);

            return match ($directive) {
                'if' => [
                    new IfToken(
                        line: $startingLine,
                    ),
                    ...$expressionLexer->parse(
                        startingLine: $startingLine,
                        operand: $expr,
                    ),
                    new EndToken(
                        line: $startingLine,
                    ),
                ],
                'elseif' => [
                    new ElseIfToken(
                        line: $startingLine,
                    ),
                    ...$expressionLexer->parse(
                        startingLine: $startingLine,
                        operand: $expr,
                    ),
                    new EndToken(
                        line: $startingLine,
                    ),
                ],
                'for' => $this->parseFor(
                    startingLine: $startingLine,
                    expression: $expr,
                    expressionLexer: $expressionLexer,
                ),
                'foreach' => $this->parseForeach(
                    startingLine: $startingLine,
                    expression: $expr,
                    expressionLexer: $expressionLexer,
                ),
                'while' => [
                    new WhileToken(
                        line: $startingLine,
                    ),
                    ...$expressionLexer->parse(
                        startingLine: $startingLine,
                        operand: $expr,
                    ),
                    new EndToken(
                        line: $startingLine,
                    ),
                ],
                'set' => [
                    new AssignToken(
                        line: $startingLine,
                    ),
                    ...$expressionLexer->parse(
                        startingLine: $startingLine,
                        operand: $expr,
                    ),
                    new EndToken(
                        line: $startingLine,
                    ),
                ],
                'break' => [
                    new BreakToken(
                        line: $startingLine,
                        op1: $this->parseLoopDepth(
                            expression: $expr,
                        ),
                    ),
                ],
                'continue' => [
                    new ContinueToken(
                        line: $startingLine,
                        op1: $this->parseLoopDepth(
                            expression: $expr,
                        ),
                    ),
                ],
                'declare' => $this->parseDeclare(
                    startingLine: $startingLine,
                    expression: $expr,
                    expressionLexer: $expressionLexer,
                ),
                default => throw LexerException::fromSequenceNotFound(
                    sequence: $directive,
                ),
            };
        }

        $directive = \mb_strtolower($expression);

        return [
            match ($directive) {
                'do' => new DoToken(
                    line: $startingLine,
                ),
                'else' => new ElseToken(
                    line: $startingLine,
                ),
                'endif' => new EndIfToken(
                    line: $startingLine,
                ),
                'endfor' => new EndForToken(
                    line: $startingLine,
                ),
                'endforeach' => new EndForeachToken(
                    line: $startingLine,
                ),
                'endwhile' => new EndWhileToken(
                    line: $startingLine,
                ),
                'break' => new BreakToken(
                    line: $startingLine,
                ),
                'continue' => new ContinueToken(
                    line: $startingLine,
                ),
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
        int $startingLine,
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
            new ForToken(
                line: $startingLine,
                op1: $value,
                op2: $key,
            ),
            ...$expressionLexer->parse(
                startingLine: $startingLine,
                operand: $expr,
            ),
            new EndToken(
                line: $startingLine,
            ),
        ];
    }

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    private function parseForeach(
        int $startingLine,
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
            new ForeachToken(
                line: $startingLine,
                op1: $value,
                op2: $key,
            ),
            ...$expressionLexer->parse(
                startingLine: $startingLine,
                operand: $expr,
            ),
            new EndToken(
                line: $startingLine,
            ),
        ];
    }

    /**
     * @return numeric-string|null
     *
     * @throws LexerException
     */
    private function parseLoopDepth(
        string $expression,
    ): ?string {
        $expression = \mb_trim($expression);

        if ($expression === '') {
            return null;
        }

        if (\preg_match('/^[1-9][0-9]*$/u', $expression) !== 1) {
            throw LexerException::fromInvalidLoopDepth();
        }

        return (string) (int) $expression;
    }

    /**
     * @return array{0: DeclareToken, 1: TokenInterface, 2: EndToken}
     *
     * @throws LexerException
     */
    private function parseDeclare(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        $parts = \explode('=', $expression, 2);

        if (\sizeof($parts) !== 2) {
            throw LexerException::fromInvalidDeclare();
        }

        $op1 = \mb_trim($parts[0]);
        $op2Raw = \mb_trim($parts[1]);

        $tokens = $expressionLexer->parse(
            startingLine: $startingLine,
            operand: $op2Raw,
        );

        if (\sizeof($tokens) !== 1) {
            throw LexerException::fromInvalidDeclareLiteral();
        }

        if ($tokens[0]->type !== BuiltinTokenNames::LITERAL->name) {
            throw LexerException::fromInvalidDeclareLiteral();
        }

        return [
            new DeclareToken(
                line: $startingLine,
                op1: $op1,
            ),
            $tokens[0],
            new EndToken(
                line: $startingLine,
            ),
        ];
    }
}
