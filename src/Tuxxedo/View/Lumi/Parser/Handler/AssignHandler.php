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

namespace Tuxxedo\View\Lumi\Parser\Handler;

use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\AssignmentOperator;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;

class AssignHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::ASSIGN->name;

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        $variableToken = $stream->current();
        if ($variableToken->type !== BuiltinTokenNames::VARIABLE->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $variableToken->type,
                expectedTokenName: BuiltinTokenNames::VARIABLE->name,
            );
        } elseif ($variableToken->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $variableName = $variableToken->op1;
        $stream->consume();

        $operatorToken = $stream->current();
        if ($operatorToken->type !== BuiltinTokenNames::OPERATOR->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $operatorToken->type,
                expectedTokenName: BuiltinTokenNames::OPERATOR->name,
            );
        } elseif ($operatorToken->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $operatorSymbol = $operatorToken->op1;
        $stream->consume();

        $assignmentOperator = null;
        $assignmentOperators = AssignmentOperator::cases();

        foreach ($assignmentOperators as $case) {
            if ($case->symbol() === $operatorSymbol) {
                $assignmentOperator = $case;

                break;
            }
        }

        if ($assignmentOperator === null && $operatorSymbol !== '=') {
            throw ParserException::fromUnexpectedSymbolOneOf(
                symbol: $operatorSymbol,
                expectedSymbols: \array_merge(
                    [
                        BinaryOperator::ASSIGN->symbol(),
                    ],
                    \array_map(
                        static fn (AssignmentOperator $operator): string => $operator->symbol(),
                        $assignmentOperators,
                    ),
                ),
            );
        }

        $expressionTokens = [];
        while (!$stream->eof() && $stream->current()->type !== BuiltinTokenNames::END->name) {
            $expressionTokens[] = $stream->current();

            $stream->consume();
        }

        if ($stream->eof()) {
            throw ParserException::fromTokenStreamEof();
        }

        $stream->consume();

        return [
            new AssignmentNode(
                name: new IdentifierNode(
                    name: $variableName,
                ),
                value: $parser->expressionParser->parse(
                    stream: new TokenStream(tokens: $expressionTokens),
                ),
                operator: $assignmentOperator,
            ),
        ];
    }
}
