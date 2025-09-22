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
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class LayoutParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::LAYOUT->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $layout = $stream->expect(BuiltinTokenNames::LAYOUT->name);

        if ($layout->op1 === null) {
            throw ParserException::fromMalformedToken(
                line: $layout->line,
            );
        }

        $total = \sizeof($stream->tokens);
        $inBlock = false;

        for ($position = 0; $position < $total; $position++) {
            $token = $stream->tokens[$position];

            if ($token->type === BuiltinTokenNames::COMMENT->name) {
                continue;
            }

            if ($token->type === BuiltinTokenNames::LAYOUT->name) {
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

            if ($token->type === BuiltinTokenNames::BLOCK->name) {
                $inBlock = true;

                continue;
            }

            if ($token->type === BuiltinTokenNames::ENDBLOCK->name) {
                $inBlock = false;

                continue;
            }

            if (
                $token->type !== BuiltinTokenNames::TEXT->name ||
                (
                    $token->op1 !== null &&
                    \preg_match('/(?s)(?=.*\s)/u', $token->op1) !== 1
                )
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
