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

use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class InvocationParser implements InvocationParserInterface
{
    public function __construct(
        private readonly ExpressionParserInterface $parser,
    ) {
    }

    public function parseFunction(
        TokenInterface $caller,
    ): void {
        if ($this->parser->stream->currentIs(BuiltinTokenNames::CHARACTER->name, ')')) {
            $this->parser->stream->consume();

            if ($caller->type !== BuiltinTokenNames::IDENTIFIER->name) {
                throw ParserException::fromUnexpectedTokenWithExpects(
                    tokenName: $caller->type,
                    expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
                );
            } elseif ($caller->op1 === null) {
                throw ParserException::fromMalformedToken();
            }

            $this->parser->state->pushNode(
                node: new FunctionCallNode(
                    name: $caller->op1,
                    arguments: [],
                ),
            );

            return;
        }

        throw ParserException::fromNotImplemented(
            feature: 'parsing function calls',
        );
    }

    public function parseMethodCall(
        TokenInterface $caller,
        TokenInterface $method,
    ): void {
        throw ParserException::fromNotImplemented(
            feature: 'parsing method calls',
        );
    }

    public function parseDereferenceChain(
        TokenInterface $caller,
        TokenInterface $method,
    ): void {
        throw ParserException::fromNotImplemented(
            feature: 'parsing dereference chain',
        );
    }
}
