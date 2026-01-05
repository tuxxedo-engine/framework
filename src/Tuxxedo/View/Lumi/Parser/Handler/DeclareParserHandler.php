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

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\DeclareToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class DeclareParserHandler implements ParserHandlerInterface
{
    /**
     * @var class-string<DeclareToken>
     */
    public private(set) string $tokenClassName = DeclareToken::class;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $directive = $stream->expect($this->tokenClassName);

        if (\sizeof($parser->state->stateStack) !== 1) {
            throw ParserException::fromDeclareTokensCannotBeNested(
                line: $directive->line,
            );
        }

        $value = $stream->expect(LiteralToken::class);

        $stream->expect(EndToken::class);

        $type = Type::fromString(
            name: $value->op2,
        );

        if ($type === null) {
            throw ParserException::fromUnexpectedToken(
                tokenName: $value->op2,
                line: $value->line,
            );
        }

        return [
            new DeclareNode(
                directive: LiteralNode::createString($directive->op1),
                value: new LiteralNode(
                    operand: $value->op1,
                    type: $type,
                ),
            ),
        ];
    }
}
