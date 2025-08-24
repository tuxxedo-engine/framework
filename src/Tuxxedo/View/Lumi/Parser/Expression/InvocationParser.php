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
use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class InvocationParser implements InvocationParserInterface
{
    // @todo Fix visibility
    public function __construct(
        public readonly ExpressionParserInterface $parser,
    ) {
    }

    public function parseSimpleFunction(
        TokenInterface $caller,
    ): FunctionCallNode {
        if ($caller->type !== BuiltinTokenNames::VARIABLE->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $caller->type,
                expectedTokenName: BuiltinTokenNames::VARIABLE->name,
            );
        } elseif ($caller->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new FunctionCallNode(
            name: $caller->op1,
            arguments: [],
        );
    }

    public function parseFunction(
        TokenInterface $caller,
    ): ExpressionNodeInterface {
        if ($this->parser->stream->currentIs(BuiltinTokenNames::CHARACTER->name, ')')) {
            $this->parser->stream->consume();

            return $this->parseSimpleFunction(
                caller: $caller,
            );
        }

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
