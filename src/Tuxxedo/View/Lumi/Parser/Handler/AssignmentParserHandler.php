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
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

// @todo This need to support property writes
class AssignmentParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::ASSIGN->name;

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        $variableToken = $stream->expect(BuiltinTokenNames::IDENTIFIER->name);

        if ($variableToken->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $variableName = $variableToken->op1;

        $operatorToken = $stream->expect(BuiltinTokenNames::OPERATOR->name);

        if ($operatorToken->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $operatorSymbol = $operatorToken->op1;

        $assignmentOperator = null;
        $assignmentOperators = AssignmentSymbol::cases();

        foreach ($assignmentOperators as $case) {
            if ($case->symbol() === $operatorSymbol) {
                $assignmentOperator = $case;

                break;
            }
        }

        if ($assignmentOperator === null) {
            throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                tokenName: $operatorSymbol,
                expectedTokenNames: \array_map(
                    static fn (AssignmentSymbol $operator): string => $operator->symbol(),
                    $assignmentOperators,
                ),
            );
        }

        $expressionTokens = [];

        while (!$stream->eof() && $stream->current()->type !== BuiltinTokenNames::END->name) {
            $expressionTokens[] = $stream->current();

            $stream->consume();
        }

        $stream->expect(BuiltinTokenNames::END->name);

        return [
            new AssignmentNode(
                name: new IdentifierNode(
                    name: $variableName,
                ),
                value: $parser->expressionParser->parse(
                    stream: new TokenStream(
                        tokens: $expressionTokens,
                    ),
                    state: $parser->state,
                ),
                operator: $assignmentOperator,
            ),
        ];
    }
}
