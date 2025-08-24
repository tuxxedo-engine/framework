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

use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class AtomicParser implements AtomicParserInterface
{
    // @todo Fix visibility
    public function __construct(
        public readonly ExpressionParserInterface $parser,
    ) {
    }

    public function parseLiteral(
        TokenInterface $literal,
    ): ExpressionNodeInterface {
        throw ParserException::fromNotImplemented(
            feature: 'parsing literals',
        );
    }

    public function parseVariable(
        TokenInterface $variable,
    ): ExpressionNodeInterface {
        throw ParserException::fromNotImplemented(
            feature: 'parsing variables',
        );
    }
}
