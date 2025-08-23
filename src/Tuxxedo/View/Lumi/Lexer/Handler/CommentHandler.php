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
use Tuxxedo\View\Lumi\Token\TextToken;

class CommentHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{#';
    }

    public function getEndingSequence(): string
    {
        return '#}';
    }

    public function tokenize(
        ByteStreamInterface $stream,
        ExpressionLexerInterface $expressionLexer,
    ): array {
        $buffer = '';

        while (!$stream->eof()) {
            if ($stream->match($this->getEndingSequence())) {
                $stream->consumeSequence($this->getEndingSequence());

                return [];
            }

            $buffer .= $stream->consume();
        }

        return [
            new TextToken($buffer),
        ];
    }
}
