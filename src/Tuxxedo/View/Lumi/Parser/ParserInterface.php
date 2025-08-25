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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\Expression\ExpressionParserInterface;
use Tuxxedo\View\Lumi\Parser\Handler\ParserHandlerInterface;

interface ParserInterface
{
    public ExpressionParserInterface $expressionParser {
        get;
    }

    public ParserStateInterface $state {
        get;
    }

    /**
     * @var ParserHandlerInterface[]
     */
    public array $handlers {
        get;
    }

    /**
     * @throws ParserException
     */
    public function parse(
        TokenStreamInterface $stream,
    ): NodeStreamInterface;
}
