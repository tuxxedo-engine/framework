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

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\TextNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;

class TextHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::TEXT->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $nodes = [];

        do {
            $token = $stream->current();

            if ($token->op1 === null) {
                throw ParserException::fromMalformedToken();
            }

            $nodes[] = new TextNode(
                text: $token->op1,
            );

            $stream->consume();
        } while (!$stream->eof() && $stream->peekIs($this->tokenName));

        return $nodes;
    }
}
