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

use Tuxxedo\View\Lumi\Lexer\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Lexer\Token\ElseToken;
use Tuxxedo\View\Lumi\Lexer\Token\EndToken;
use Tuxxedo\View\Lumi\Lexer\Token\ForToken;
use Tuxxedo\View\Lumi\Lexer\Token\IfToken;
use Tuxxedo\View\Lumi\Lexer\Token\AssignToken;
use Tuxxedo\View\Lumi\Lexer\Token\TextToken;
use Tuxxedo\View\Lumi\Lexer\Token\TokenInterface;
use Tuxxedo\View\Lumi\Lexer\Token\WhileToken;
use Tuxxedo\View\Lumi\ByteStreamInterface;

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

            return [
                match ($directive) {
                    'if' => new IfToken(),
                    'elseif' => new ElseIfToken(),
                    'for' => new ForToken(),
                    'while' => new WhileToken(),
                    'set' => new AssignToken(),
                    default => throw LexerException::fromSequenceNotFound(
                        sequence: $directive,
                    ),
                },
                ...$expressionLexer->parse($expr),
                new EndToken(),
            ];
        }

        $directive = \mb_strtolower($expression);

        return [
            match ($directive) {
                'else' => new ElseToken(),
                'endif', 'endfor', 'endwhile' => new EndToken(),
                default => throw LexerException::fromSequenceNotFound(
                    sequence: $directive,
                ),
            },
        ];
    }
}
