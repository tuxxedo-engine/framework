<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Parser\Handler;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\YieldNode;
use Tuxxedo\View\Lumi\Syntax\Token\YieldToken;

class YieldParserHandler implements ParserHandlerInterface
{
    /**
     * @var class-string<YieldToken>
     */
    public private(set) string $tokenClassName = YieldToken::class;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $yield = $stream->expect($this->tokenClassName);

        return [
            new YieldNode(
                name: $yield->op1,
            ),
        ];
    }
}
