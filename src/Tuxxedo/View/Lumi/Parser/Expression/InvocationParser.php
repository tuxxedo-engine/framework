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

class InvocationParser implements InvocationParserInterface
{
    // @todo Fix visibility
    public function __construct(
        public readonly ExpressionParserInterface $parser,
    ) {
    }

    public function parseFunction(
        TokenInterface $caller,
    ): ExpressionNodeInterface {
        throw ParserException::fromNotImplemented(
            feature: 'parsing function calls',
        );
    }

    public function parseMethodCall(
        TokenInterface $caller,
        TokenInterface $method,
    ): ExpressionNodeInterface {
        throw ParserException::fromNotImplemented(
            feature: 'parsing method calls',
        );
    }

    public function parseDereferenceChain(
        TokenInterface $caller,
        TokenInterface $method,
    ): ExpressionNodeInterface {
        throw ParserException::fromNotImplemented(
            feature: 'parsing dereference chain',
        );
    }
}
