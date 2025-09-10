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

        return [
            new LayoutNode(
                file: $layout->op1,
            ),
        ];
    }
}
