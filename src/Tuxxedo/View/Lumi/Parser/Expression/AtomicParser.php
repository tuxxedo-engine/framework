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
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class AtomicParser implements AtomicParserInterface
{
    public function __construct(
        private readonly ExpressionParserInterface $parser,
    ) {
    }

    public function parseSimpleLiteral(
        TokenInterface $literal,
    ): LiteralNode {
        if ($literal->op1 === null || $literal->op2 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new LiteralNode(
            operand: $literal->op1,
            type: NodeNativeType::fromTokenNativeType($literal->op2),
        );
    }

    public function parseLiteral(
        TokenInterface $literal,
    ): ExpressionNodeInterface {
        if ($this->parser->stream->eof()) {
            return $this->parseSimpleLiteral($literal);
        }

        throw ParserException::fromNotImplemented(
            feature: 'complex parsing literals',
        );
    }

    public function parseSimpleVariable(
        TokenInterface $variable,
    ): IdentifierNode {
        if ($variable->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new IdentifierNode(
            name: $variable->op1,
        );
    }

    public function parseVariable(
        TokenInterface $variable,
    ): ExpressionNodeInterface {
        if ($this->parser->stream->eof()) {
            return $this->parseSimpleVariable($variable);
        }

        throw ParserException::fromNotImplemented(
            feature: 'parsing complex variables',
        );
    }
}
