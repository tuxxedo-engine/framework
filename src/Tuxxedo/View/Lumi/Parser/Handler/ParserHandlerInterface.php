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
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

interface ParserHandlerInterface
{
    public string $tokenName {
        get;
    }

    /**
     * @return NodeInterface[]
     *
     * @throws ParserException
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array;
}
