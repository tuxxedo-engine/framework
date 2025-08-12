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

use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\Tokens\ElseIfToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\ElseToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\EndForToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\EndIfToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\EndWhileToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\ForToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\IfToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\IncludeToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\TextToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\TokenInterface;
use Tuxxedo\View\Lumi\Lexer\Tokens\WhileToken;
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;

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

    public function tokenize(TokenStreamInterface $stream): array
    {
        $buffer = '';

        while (!$stream->eof()) {
            if ($stream->match($this->getEndingSequence())) {
                $stream->consumeSequence($this->getEndingSequence());

                return [
                    $this->parseBlock(\mb_trim($buffer)),
                ];
            }

            $buffer .= $stream->consume();
        }

        return [
            new TextToken($this->getStartingSequence() . $buffer),
        ];
    }

    private function parseBlock(string $expression): TokenInterface
    {
        if (\mb_strpos($expression, ' ') !== false) {
            [$directive, $expr] = \explode(' ', $expression, 2);
            $directive = \mb_strtolower($directive);

            return match ($directive) {
                'if' => new IfToken($expr),
                'elseif' => new ElseIfToken($expr),
                'for' => new ForToken($expr),
                'while' => new WhileToken($expr),
                'include' => new IncludeToken($expr),
                default => throw LexerException::fromSequenceNotFound(sequence: $directive),
            };
        }

        $directive = \mb_strtolower($expression);

        return match ($directive) {
            'else' => new ElseToken(),
            'endif' => new EndIfToken(),
            'endfor' => new EndForToken(),
            'endwhile' => new EndWhileToken(),
            default => throw LexerException::fromSequenceNotFound(sequence: $directive),
        };
    }
}
