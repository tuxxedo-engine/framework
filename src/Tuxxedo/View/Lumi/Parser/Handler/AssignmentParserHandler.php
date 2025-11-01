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
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\AssignToken;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class AssignmentParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenClassName = AssignToken::class;

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        $variableToken = $stream->expect(IdentifierToken::class);

        if ($stream->currentIs(CharacterToken::class, CharacterSymbol::DOT->symbol())) {
            $accessor = $this->parsePropertyAccess(
                stream: $stream,
                accessor: $variableToken->op1,
            );
        } elseif ($stream->currentIs(CharacterToken::class, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
            $accessor = $this->parseArrayAccess(
                stream: $stream,
                accessor: $variableToken->op1,
            );
        } else {
            $accessor = new IdentifierNode(
                name: $variableToken->op1,
            );
        }

        $assignmentOperator = null;
        $assignmentOperators = AssignmentSymbol::cases();
        $operatorToken = $stream->expect(OperatorToken::class);

        foreach ($assignmentOperators as $case) {
            if ($case->symbol() === $operatorToken->op1) {
                $assignmentOperator = $case;

                break;
            }
        }

        if ($assignmentOperator === null) {
            throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                tokenName: $operatorToken->op1,
                expectedTokenNames: \array_map(
                    static fn (AssignmentSymbol $operator): string => $operator->symbol(),
                    $assignmentOperators,
                ),
                line: $operatorToken->line,
            );
        }

        $expressionTokens = [];

        while (
            !$stream->eof() &&
            !$stream->current() instanceof EndToken
        ) {
            $expressionTokens[] = $stream->current();

            $stream->consume();
        }

        $stream->expect(EndToken::class);

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
        $stream->expect(CharacterToken::class, CharacterSymbol::DOT->symbol());

        $node = new PropertyAccessNode(
            accessor: \is_string($accessor)
                ? new IdentifierNode(
                    name: $accessor,
                ) : $accessor,
            property: $stream->expect(IdentifierToken::class)->op1,
        );

        if ($stream->currentIs(CharacterToken::class, CharacterSymbol::DOT->symbol())) {
            return $this->parsePropertyAccess(
                stream: $stream,
                accessor: $node,
            );
        } elseif ($stream->currentIs(CharacterToken::class, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
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
        $stream->expect(CharacterToken::class, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol());

        $key = $this->parseConstrainedIndex($stream);

        $stream->expect(CharacterToken::class, CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol());

        $node = new ArrayAccessNode(
            array: \is_string($accessor)
                ? new IdentifierNode(
                    name: $accessor,
                ) : $accessor,
            key: $key,
        );

        if ($stream->currentIs(CharacterToken::class, CharacterSymbol::DOT->symbol())) {
            return $this->parsePropertyAccess($stream, $node);
        } elseif ($stream->currentIs(CharacterToken::class, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
            return $this->parseArrayAccess($stream, $node);
        }

        return $node;
    }

    private function parseConstrainedIndex(
        TokenStreamInterface $stream,
    ): IdentifierNode|LiteralNode|PropertyAccessNode|ArrayAccessNode {
        $node = $this->parseIndexPrimary($stream);

        while (!$stream->eof()) {
            if ($stream->currentIs(CharacterToken::class, CharacterSymbol::DOT->symbol())) {
                $stream->consume();

                $node = new PropertyAccessNode(
                    accessor: $node,
                    property: $stream->expect(IdentifierToken::class)->op1,
                );
            } elseif ($stream->currentIs(CharacterToken::class, CharacterSymbol::LEFT_SQUARE_BRACKET->symbol())) {
                $stream->consume();

                $key = $this->parseConstrainedIndex($stream);

                $stream->expect(CharacterToken::class, CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol());

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
        if ($stream->currentIs(IdentifierToken::class)) {
            $identifier = $stream->expect(IdentifierToken::class);

            return new IdentifierNode(
                name: $identifier->op1,
            );
        }

        if ($stream->currentIs(LiteralToken::class)) {
            $literal = $stream->expect(LiteralToken::class);

            return new LiteralNode(
                operand: $literal->op1,
                type: Type::fromString(
                    name: $literal->op2,
                    line: $literal->line,
                ),
            );
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $stream->current()::name(),
            expectedTokenNames: [
                IdentifierToken::name(),
                LiteralToken::name(),
            ],
            line: $stream->current()->line,
        );
    }
}
