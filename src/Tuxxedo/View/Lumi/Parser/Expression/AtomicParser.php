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

use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class AtomicParser implements AtomicParserInterface
{
    public function __construct(
        private readonly ExpressionParserInterface $parser,
    ) {
    }

    private function literalTokenToNode(
        TokenInterface $literal,
    ): LiteralNode {
        if ($literal->type !== BuiltinTokenNames::LITERAL->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $literal->type,
                expectedTokenName: BuiltinTokenNames::LITERAL->name,
            );
        } elseif ($literal->op1 === null || $literal->op2 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new LiteralNode(
            operand: $literal->op1,
            type: NodeNativeType::fromTokenNativeType($literal->op2),
        );
    }

    public function parseLiteral(
        TokenInterface $literal,
    ): void {
        if ($this->parser->stream->eof()) {
            $this->parser->state->pushNode(
                node: $this->literalTokenToNode($literal),
            );

            return;
        } elseif ($this->parser->stream->currentIs(BuiltinTokenNames::OPERATOR->name)) {
            $token = $this->parser->stream->expect(BuiltinTokenNames::OPERATOR->name);

            if (!BinaryOperator::is($token)) {
                throw ParserException::fromNotImplemented(
                    feature: 'parsing literals from non binary operators ahead',
                );
            }

            $this->parser->operator->parseBinaryByNode(
                left: $this->literalTokenToNode($literal),
                operator: BinaryOperator::from($token),
            );

            return;
        }

        throw ParserException::fromNotImplemented(
            feature: 'complex parsing literals',
        );
    }

    private function variableTokenToNode(
        TokenInterface $variable,
    ): IdentifierNode {
        if ($variable->type !== BuiltinTokenNames::IDENTIFIER->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $variable->type,
                expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
            );
        } elseif ($variable->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new IdentifierNode(
            name: $variable->op1,
        );
    }

    public function parseVariable(
        TokenInterface $variable,
    ): void {
        if ($this->parser->stream->eof()) {
            $this->parser->state->pushNode(
                node: $this->variableTokenToNode($variable),
            );

            return;
        }

        throw ParserException::fromNotImplemented(
            feature: 'parsing complex variables',
        );
    }
}
