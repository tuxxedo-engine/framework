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

use Tuxxedo\View\Lumi\Lexer\Tokens\EchoToken;
use Tuxxedo\View\Lumi\Lexer\Tokens\TextToken;
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;

class EchoHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{{';
    }

    public function getEndingSequence(): string
    {
        return '}}';
    }

    public function tokenize(TokenStreamInterface $stream): array
    {
        $buffer = '';

        while (!$stream->eof()) {
            if ($stream->match($this->getEndingSequence())) {
                $stream->consumeSequence($this->getEndingSequence());

                return [
                    new EchoToken(\mb_trim($buffer)),
                ];
            }

            $buffer .= $stream->consume();
        }

        return [
            new TextToken($this->getStartingSequence() . $buffer),
        ];
    }
}
