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
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

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
            throw ParserException::fromMalformedToken(
                line: $variableToken->line,
            );
        }

        if ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::DOT->symbol())) {
            $accessor = $this->parsePropertyAccess(
                stream: $stream,
                accessor: $variableToken->op1,
            );
        } elseif ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
            $accessor = $this->parseArrayAccess(
                stream: $stream,
                accessor: $variableToken->op1,
            );
        } else {
            $accessor = new IdentifierNode(
                name: $variableToken->op1,
            );
        }

        $operatorToken = $stream->expect(BuiltinTokenNames::OPERATOR->name);

        if ($operatorToken->op1 === null) {
            throw ParserException::fromMalformedToken(
                line: $operatorToken->line,
            );
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
                line: $operatorToken->line,
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
                name: $accessor,
                value: $parser->expressionParser->parse(
                    stream: new TokenStream(
                        tokens: $expressionTokens,
                    ),
                    startingLine: $stream->tokens[$stream->position - 1]->line,
                ),
                operator: $assignmentOperator,
            ),
        ];
    }

    private function parsePropertyAccess(
        TokenStreamInterface $stream,
        PropertyAccessNode|ArrayAccessNode|string $accessor,
    ): ArrayAccessNode|PropertyAccessNode {
        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::DOT->symbol());

        $property = $stream->expect(BuiltinTokenNames::IDENTIFIER->name);

        if ($property->op1 === null) {
            throw ParserException::fromMalformedToken(
                line: $property->line,
            );
        }

        $node = new PropertyAccessNode(
            accessor: \is_string($accessor)
                ? new IdentifierNode(
                    name: $accessor,
                ) : $accessor,
            property: $property->op1,
        );

        if ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::DOT->symbol())) {
            return $this->parsePropertyAccess(
                stream: $stream,
                accessor: $node,
            );
        } elseif ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
            return $this->parseArrayAccess(
                stream: $stream,
                accessor: $node,
            );
        }

        return $node;
    }

    private function parseArrayAccess(
        TokenStreamInterface $stream,
        PropertyAccessNode|ArrayAccessNode|string $accessor,
    ): ArrayAccessNode|PropertyAccessNode {
        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol());

        $key = $this->parseConstrainedIndex($stream);

        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol());

        $node = new ArrayAccessNode(
            array: \is_string($accessor)
                ? new IdentifierNode(
                    name: $accessor,
                ) : $accessor,
            key: $key,
        );

        if ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::DOT->symbol())) {
            return $this->parsePropertyAccess($stream, $node);
        } elseif ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
            return $this->parseArrayAccess($stream, $node);
        }

        return $node;
    }

    private function parseConstrainedIndex(
        TokenStreamInterface $stream,
    ): IdentifierNode|LiteralNode|PropertyAccessNode|ArrayAccessNode {
        $node = $this->parseIndexPrimary($stream);

        while (!$stream->eof()) {
            if ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::DOT->symbol())) {
                $stream->consume();

                $property = $stream->expect(BuiltinTokenNames::IDENTIFIER->name);

                if ($property->op1 === null) {
                    throw ParserException::fromMalformedToken(
                        line: $property->line,
                    );
                }

                $node = new PropertyAccessNode(
                    accessor: $node,
                    property: $property->op1,
                );
            } elseif ($stream->currentIs(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
                $stream->consume();

                $key = $this->parseConstrainedIndex($stream);

                $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol());

                $node = new ArrayAccessNode(
                    array: $node,
                    key: $key,
                );
            } else {
                break;
            }
        }

        return $node;
    }

    /**
     * @throws ParserException
     */
    private function parseIndexPrimary(
        TokenStreamInterface $stream,
    ): IdentifierNode|LiteralNode {
        if ($stream->currentIs(BuiltinTokenNames::IDENTIFIER->name)) {
            $identifier = $stream->expect(BuiltinTokenNames::IDENTIFIER->name);

            if ($identifier->op1 === null) {
                throw ParserException::fromMalformedToken(
                    line: $identifier->line,
                );
            }

            return new IdentifierNode(
                name: $identifier->op1,
            );
        }

        if ($stream->currentIs(BuiltinTokenNames::LITERAL->name)) {
            $literal = $stream->expect(BuiltinTokenNames::LITERAL->name);

            if ($literal->op1 === null || $literal->op2 === null) {
                throw ParserException::fromMalformedToken(
                    line: $literal->line,
                );
            }

            return new LiteralNode(
                operand: $literal->op1,
                type: NativeType::fromString(
                    name: $literal->op2,
                    line: $literal->line,
                ),
            );
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $stream->current()->type,
            expectedTokenNames: [
                BuiltinTokenNames::IDENTIFIER->name,
                BuiltinTokenNames::LITERAL->name,
            ],
            line: $stream->current()->line,
        );
    }
}
