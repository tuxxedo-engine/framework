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
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndBlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\LayoutToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class LayoutParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenClassName = LayoutToken::class;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $layout = $stream->expect(LayoutToken::class);

        if (\sizeof($parser->state->stateStack) !== 1) {
            throw ParserException::fromLayoutModeMustBeRoot(
                line: $layout->line,
            );
        }

        $total = \sizeof($stream->tokens);
        $inBlock = false;

        for ($position = 0; $position < $total; $position++) {
            $token = $stream->tokens[$position];

            if ($token instanceof CommentToken) {
                continue;
            }

            if ($token instanceof LayoutToken) {
                if ($token !== $layout) {
                    throw ParserException::fromLayoutModeMustOnlyHaveOneLayout(
                        line: $token->line,
                    );
                }

                continue;
            }

            if ($inBlock) {
                continue;
            }

            if ($token instanceof BlockToken) {
                $inBlock = true;

                continue;
            }

            if ($token instanceof EndBlockToken) {
                $inBlock = false;

                continue;
            }

            if (
                !$token instanceof TextToken ||
                \preg_match('/(?s)(?=.*\s)/u', $token->op1) !== 1
            ) {
                throw ParserException::fromLayoutModeMustNotHaveRootElements(
                    line: $token->line,
                );
            }
        }

        return [
            new LayoutNode(
                file: $layout->op1,
            ),
        ];
    }
}
