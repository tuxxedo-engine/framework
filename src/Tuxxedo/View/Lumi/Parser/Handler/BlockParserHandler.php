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
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class BlockParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::BLOCK->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $block = $stream->expect(BuiltinTokenNames::BLOCK->name);

        if ($block->op1 === null) {
            throw ParserException::fromMalformedToken(
                line: $block->line,
            );
        }

        $body = [];

        while (!$stream->eof()) {
            $token = $stream->consume();

            if ($token->type === BuiltinTokenNames::BLOCK->name) {
                throw ParserException::fromBlockTokensCannotBeNested(
                    line: $token->line,
                );
            } elseif ($token->type === BuiltinTokenNames::ENDBLOCK->name) {
                break;
            }

            $body[] = $token;
        }

        return [
            new BlockNode(
                name: $block->op1,
                body: $parser->parse(
                    stream: new TokenStream(
                        tokens: $body,
                    ),
                )->nodes,
            ),
        ];
    }
}
