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
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\DoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Syntax\Token\WhileToken;

class DoWhileParserHandler extends AbstractWhileParserHandler
{
    public private(set) string $tokenClassName = DoToken::class;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        $remainingBody = $this->doBodyReadAhead($stream);

        $body = [];

        while ($remainingBody-- > 0) {
            $body[] = $stream->current();

            $stream->consume();
        }

        $stream->expect(WhileToken::class);

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
            $token = $stream->tokens[$position];

            if (
                $token instanceof DoToken ||
                $token instanceof WhileToken ||
                $token instanceof EndWhileToken
            ) {
                $controlTokens[] = [
                    'token' => $token::class,
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
                $controlTokens[$i]['token'] === DoToken::class &&
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
            $token = $controlTokens[$i]['token'];

            if ($token === WhileToken::class) {
                $whileStack[] = $i;

                continue;
            }

            if ($token === EndWhileToken::class) {
                if (\sizeof($whileStack) === 0) {
                    continue;
                }

                $headerIndex = \array_pop($whileStack);
                $controlTokens[$headerIndex] = [
                    'token' => $controlTokens[$headerIndex]['token'],
                    'position' => $controlTokens[$headerIndex]['position'],
                    'isHeaderWhile' => true,
                ];
            }
        }

        $doDepth = 1;
        $index = 0;
        $skippedFirstDo = false;

        while ($index < $count) {
            $control = $controlTokens[$index];

            if ($control['token'] === DoToken::class) {
                if (!$skippedFirstDo) {
                    $skippedFirstDo = true;
                } else {
                    $doDepth++;
                }

                $index++;

                continue;
            }
            if ($control['token'] === WhileToken::class) {
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
