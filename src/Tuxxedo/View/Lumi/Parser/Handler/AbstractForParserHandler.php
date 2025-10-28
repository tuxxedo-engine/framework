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
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\IterableExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\EndForToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForeachToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForeachToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;

abstract class AbstractForParserHandler implements ParserHandlerInterface
{
    /**
     * @param class-string<EndForToken|EndForeachToken> $endTokenClassName
     * @return NodeInterface[]
     *
     * @throws ParserException
     */
    protected function parseLoop(
        ParserInterface $parser,
        TokenStreamInterface $stream,
        string $endTokenClassName,
    ): array {
        /** @var ForToken|ForeachToken $startToken */
        $startToken = $stream->expect($this->tokenClassName);

        $value = $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: [
                    new IdentifierToken(
                        line: $startToken->line,
                        op1: $startToken->op1,
                    ),
                ],
            ),
            startingLine: $startToken->line,
        );

        if (!$value instanceof IdentifierNode) {
            throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                tokenName: $startToken->op1,
                expectedTokenNames: [
                    IdentifierToken::name(),
                ],
                line: $startToken->line,
            );
        }

        if ($startToken->op2 !== null) {
            $key = $parser->expressionParser->parse(
                stream: new TokenStream(
                    tokens: [
                        new IdentifierToken(
                            line: $startToken->line,
                            op1: $startToken->op2,
                        ),
                    ],
                ),
                startingLine: $startToken->line,
            );

            if (!$key instanceof IdentifierNode) {
                throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                    tokenName: $startToken->op2,
                    expectedTokenNames: [
                        IdentifierToken::name(),
                    ],
                    line: $startToken->line,
                );
            }
        }

        $expressionTokens = [];

        while (
            !$stream->eof() &&
            !$stream->current() instanceof EndToken
        ) {
            $expressionTokens[] = $stream->current();

            $stream->consume();
        }

        $stream->expect(EndToken::class);
        $parser->state->enterLoop();

        if (\sizeof($expressionTokens) === 0) {
            throw ParserException::fromEmptyExpression(
                line: $stream->tokens[$stream->position - 1]->line,
            );
        }

        $iterator = $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: $expressionTokens,
            ),
            startingLine: $stream->tokens[$stream->position - 1]->line,
        );

        if (!$iterator instanceof IterableExpressionNodeInterface) {
            throw ParserException::fromExpressionNotIterable(
                line: $expressionTokens[\array_key_first($expressionTokens)]->line,
            );
        }

        $bodyTokens = [];

        while (!$stream->currentIs($endTokenClassName)) {
            $bodyTokens[] = $stream->current();

            $stream->consume();
        }

        $body = $parser->parse(
            stream: new TokenStream(
                tokens: $bodyTokens,
            ),
        )->nodes;

        $stream->expect($endTokenClassName);
        $parser->state->leaveLoop();

        return [
            new ForNode(
                value: $value,
                iterator: $iterator,
                body: $body,
                key: $key ?? null,
            ),
        ];
    }
}
