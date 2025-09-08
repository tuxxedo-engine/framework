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

// @todo Split this like AbstractForParse
// @todo Optimize the do-while initial loop prior to reduction
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
            throw ParserException::fromEmptyExpression(
                line: $stream->current()->line,
            );
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
            startingLine: $stream->tokens[$stream->position - 1]->line,
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
    private function doBodyReadAhead(TokenStreamInterface $stream): int
    {
        $controlTokens = [];
        $total = \sizeof($stream->tokens);

        for ($position = 0; $position < $total; $position++) {
            $type = $stream->tokens[$position]->type;

            if (
                $type === BuiltinTokenNames::DO->name ||
                $type === BuiltinTokenNames::WHILE->name ||
                $type === BuiltinTokenNames::ENDWHILE->name
            ) {
                $controlTokens[] = [
                    'type' => $type,
                    'position' => $position,
                    'isHeaderWhile' => false,
                ];
            }
        }

        $currentDoPosition = $stream->position - 1;

        $startIndex = 0;
        $count = \sizeof($controlTokens);

        for ($i = 0; $i < $count; $i++) {
            if (
                $controlTokens[$i]['type'] === BuiltinTokenNames::DO->name &&
                $controlTokens[$i]['position'] === $currentDoPosition
            ) {
                $startIndex = $i;

                break;
            }
        }

        $controlTokens = \array_slice($controlTokens, $startIndex);
        $count = \sizeof($controlTokens);

        $whileStack = [];
        for ($i = 0; $i < $count; $i++) {
            $type = $controlTokens[$i]['type'];

            if ($type === BuiltinTokenNames::WHILE->name) {
                $whileStack[] = $i;

                continue;
            }

            if ($type === BuiltinTokenNames::ENDWHILE->name) {
                if (\sizeof($whileStack) === 0) {
                    continue;
                }

                $headerIndex = \array_pop($whileStack);
                $controlTokens[$headerIndex]['isHeaderWhile'] = true;
            }
        }

        $doDepth = 1;
        $index = 0;
        $skippedFirstDo = false;

        while ($index < $count) {
            $control = $controlTokens[$index];

            if ($control['type'] === BuiltinTokenNames::DO->name) {
                if (!$skippedFirstDo) {
                    $skippedFirstDo = true;
                } else {
                    $doDepth++;
                }

                $index++;

                continue;
            }
            if ($control['type'] === BuiltinTokenNames::WHILE->name) {
                if ($control['isHeaderWhile']) {
                    $index++;

                    continue;
                }

                $doDepth--;

                if ($doDepth === 0) {
                    return $control['position'] - $currentDoPosition - 1;
                }

                $index++;

                continue;
            }

            $index++;
        }

        return 0;
    }
}
