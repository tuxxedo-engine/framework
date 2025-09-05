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

use Tuxxedo\View\Lumi\Node\GroupNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class GroupingParser implements GroupingParserInterface
{
    public function __construct(
        private readonly ExpressionParserInterface $parser,
    ) {
    }

    public function parseGroup(): void
    {
        if (
            (
                $this->parser->stream->currentIs(BuiltinTokenNames::LITERAL->name) ||
                $this->parser->stream->currentIs(BuiltinTokenNames::IDENTIFIER->name)
            ) &&
            $this->parser->stream->peekIs(BuiltinTokenNames::CHARACTER->name, ')')
        ) {
            $const = $this->parser->stream->consume();
            $this->parser->stream->consume();

            if ($const->type === BuiltinTokenNames::LITERAL->name) {
                $this->parser->atomic->parseLiteral($const);
            } else {
                $this->parser->atomic->parseVariable($const);
            }

            $this->parser->state->pushNode(
                new GroupNode(
                    operand: $this->parser->state->popNode(),
                ),
            );

            return;
        }

        throw ParserException::fromNotImplemented(
            feature: 'parsing complex group expressions',
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
