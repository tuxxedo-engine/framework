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

namespace Tuxxedo\View\Lumi\Parser\Expression;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;

interface ExpressionParserInterface
{
    /**
     * @throws ParserException
     */
    public function parse(
        TokenStreamInterface $stream,
    ): ExpressionNodeInterface;
}
