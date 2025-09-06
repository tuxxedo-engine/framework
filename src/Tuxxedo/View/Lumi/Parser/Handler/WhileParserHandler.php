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
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

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

        $condition = $this->collectCondition(
            parser: $parser,
            stream: $stream,
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
     * @throws ParserException
     */
    private function collectCondition(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): ExpressionNodeInterface {
        if ($stream->currentIs(BuiltinTokenNames::END->name)) {
            throw ParserException::fromEmptyExpression();
        }

        $tokens = [];

        do {
            $tokens[] = $stream->current();

            $stream->consume();
        } while (!$stream->currentIs(BuiltinTokenNames::END->name));

        $stream->expect(BuiltinTokenNames::END->name);

        return $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: $tokens,
            ),
            state: $parser->state,
        );
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
        $remainingBody = $this->doBodyReadAhead($stream);

        $body = [];

        while ($remainingBody-- > 0) {
            $body[] = $stream->current();

            $stream->consume();
        }

        $stream->expect(BuiltinTokenNames::WHILE->name);

        $parser->state->enterLoop();

        $body = $parser->parse(
            stream: new TokenStream(
                tokens: $body,
            ),
        )->nodes;

        $parser->state->leaveLoop();

        return [
            new DoWhileNode(
                operand: $this->collectCondition(
                    parser: $parser,
                    stream: $stream,
                ),
                body: $body,
            ),
        ];
    }

    /**
     * @throws ParserException
     */
    private function doBodyReadAhead(
        TokenStreamInterface $stream,
    ): int {
        // @todo This code is not robust enough to work with everything in hello_world_while_more.lumi
        $position = 0;
        $sawWhileOrEndWhile = false;
        $lastToken = null;

        /** @var array<'DO'|'WHILE'> $stack */
        $stack = [
            BuiltinTokenNames::DO->name,
        ];

        while (true) {
            $token = $stream->peek($position);

            if ($token === null) {
                if (!$sawWhileOrEndWhile) {
                    return 0;
                }

                throw ParserException::fromUnexpectedTokenWithExpects(
                    tokenName: $lastToken->type ?? BuiltinTokenNames::DO->name,
                    expectedTokenName: BuiltinTokenNames::WHILE->name,
                );
            }

            $type = $token->type;

            if ($type === BuiltinTokenNames::DO->name) {
                $stack[] = BuiltinTokenNames::DO->name;
            } elseif ($type === BuiltinTokenNames::ENDWHILE->name) {
                $sawWhileOrEndWhile = true;

                if (\end($stack) === BuiltinTokenNames::WHILE->name) {
                    \array_pop($stack);
                }
            } elseif ($type === BuiltinTokenNames::WHILE->name) {
                $sawWhileOrEndWhile = true;

                if (\end($stack) === BuiltinTokenNames::DO->name) {
                    \array_pop($stack);

                    if (\sizeof($stack) === 0) {
                        return $position;
                    }
                } else {
                    $stack[] = BuiltinTokenNames::WHILE->name;
                }
            }

            $position++;
            $lastToken = $token;
        }
    }
}
