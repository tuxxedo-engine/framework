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
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class WhileParserHandler implements ParserHandlerInterface
{
    /**
     * @param 'DO'|'WHILE' $tokenName
     */
    public function __construct(
        public readonly string $tokenName,
    ) {
    }

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        if ($this->tokenName === BuiltinTokenNames::DO->name) {
            return $this->parseDoWhile(
                parser: $parser,
                stream: $stream,
            );
        }

        $tokens = $this->collectCondition($stream);

        $stream->expect(BuiltinTokenNames::END->name);
        $parser->state->enterLoop();

        $condition = $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: $tokens,
            ),
            state: $parser->state,
        );

        $bodyTokens = [];

        while (!$stream->currentIs(BuiltinTokenNames::ENDWHILE->name)) {
            $bodyTokens[] = $stream->current();

            $stream->consume();
        }

        $parser->state->enterLoop();

        $body = $parser->parse(
            stream: new TokenStream(
                tokens: $bodyTokens,
            ),
        )->nodes;

        $stream->expect(BuiltinTokenNames::ENDWHILE->name);
        $parser->state->leaveLoop();

        return [
            new WhileNode(
                operand: $condition,
                body: $body,
            ),
        ];
    }

    /**
     * @return TokenInterface[]
     *
     * @throws ParserException
     */
    private function collectCondition(
        TokenStreamInterface $stream,
    ): array {
        if ($stream->currentIs(BuiltinTokenNames::END->name)) {
            throw ParserException::fromEmptyExpression();
        }

        $tokens = [];

        do {
            $tokens[] = $stream->current();

            $stream->consume();
        } while (!$stream->currentIs(BuiltinTokenNames::END->name));

        return $tokens;
    }

    /**
     * @return NodeInterface[]
     *
     * @throws ParserException
     */
    private function parseDoWhile(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        throw ParserException::fromNotImplemented(
            feature: 'Do while loops',
        );
    }
}
