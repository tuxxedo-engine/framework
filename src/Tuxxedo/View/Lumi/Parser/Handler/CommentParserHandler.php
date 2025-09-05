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
use Tuxxedo\View\Lumi\Node\CommentNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class CommentParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::COMMENT->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $nodes = [];

        do {
            $token = $stream->expect(BuiltinTokenNames::COMMENT->name);

            if ($token->op1 === null) {
                throw ParserException::fromMalformedToken();
            }

            $nodes[] = new CommentNode(
                text: $token->op1,
            );
        } while (!$stream->eof() && $stream->currentIs($this->tokenName));

        return $nodes;
    }
}
