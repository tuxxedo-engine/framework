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

namespace Tuxxedo\View\Lumi\Parser\OldExpression;

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class OperatorParser implements OperatorParserInterface
{
    public function __construct(
        private readonly ExpressionParserInterface $parser,
    ) {
    }

    private function parseBinaryRight(): ExpressionNodeInterface
    {
        $next = $this->parser->stream->current();
        $this->parser->stream->consume();

        if (
            $next->type !== BuiltinTokenNames::IDENTIFIER->name &&
            $next->type !== BuiltinTokenNames::LITERAL->name
        ) {
            throw ParserException::fromNotImplemented(
                feature: 'only variables and literals are supported in binary operations on the right hand side',
            );
        }

        if ($next->type === BuiltinTokenNames::IDENTIFIER->name) {
            if ($next->op1 === null) {
                throw ParserException::fromMalformedToken();
            }

            return new IdentifierNode(
                name: $next->op1,
            );
        } else {
            if ($next->op1 === null || $next->op2 === null) {
                throw ParserException::fromMalformedToken();
            }

            return new LiteralNode(
                operand: $next->op1,
                type: NativeType::fromString($next->op2),
            );
        }
    }

    public function parseBinaryByNode(
        ExpressionNodeInterface $left,
        BinarySymbol $operator,
    ): void {
        if ($operator === BinarySymbol::CONCAT) {
            $this->parseConcat(
                operands: [
                    $left,
                ],
            );

            return;
        }

        $this->parser->state->pushNode(
            new BinaryOpNode(
                left: $left,
                right: $this->parseBinaryRight(),
                operator: $operator,
            ),
        );
    }

    public function parseBinaryByToken(
        TokenInterface $left,
        BinarySymbol $operator,
    ): void {
        if ($left->type !== BuiltinTokenNames::IDENTIFIER->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $left->type,
                expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
            );
        } elseif ($left->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $this->parser->state->pushNode(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: $left->op1,
                ),
                right: $this->parseBinaryRight(),
                operator: $operator,
            ),
        );
    }

    public function parseUnary(
        UnarySymbol $operator,
        TokenInterface $operand,
    ): void {
        throw ParserException::fromNotImplemented(
            feature: 'parsing unary expressions',
        );
    }

    public function parseConcat(
        array $operands,
    ): void {
        throw ParserException::fromNotImplemented(
            feature: 'Concatenation is not implemented',
        );
    }
}
