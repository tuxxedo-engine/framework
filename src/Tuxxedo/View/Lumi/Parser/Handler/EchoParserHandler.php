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

namespace Tuxxedo\View\Lumi\Parser\Handler;

use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Node\EchoNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class EchoParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::ECHO->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        $tokens = [];

        while (!$stream->eof()) {
            $token = $stream->current();

            if ($token->type === BuiltinTokenNames::END->name) {
                $stream->consume();

                break;
            }

            $tokens[] = $stream->current();

            $stream->consume();
        }

        return [
            new EchoNode(
                operand: $parser->expressionParser->parse(
                    stream: new TokenStream(
                        tokens: $tokens,
                    ),
                    state: $parser->state,
                ),
            ),
        ];
    }
}
