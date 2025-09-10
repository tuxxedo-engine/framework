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
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class DeclareParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::DECLARE->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $directive = $stream->current();

        if ($directive->op1 === null) {
            throw ParserException::fromMalformedToken(
                line: $directive->line,
            );
        }

        $stream->consume();

        $value = $stream->expect(BuiltinTokenNames::LITERAL->name);

        $stream->expect(BuiltinTokenNames::END->name);

        if (
            $value->op1 === null ||
            $value->op2 === null
        ) {
            throw ParserException::fromMalformedToken(
                line: $directive->line,
            );
        }

        return [
            new DeclareNode(
                directive: new LiteralNode(
                    operand: $directive->op1,
                    type: NativeType::STRING,
                ),
                value: new LiteralNode(
                    operand: $value->op1,
                    type: NativeType::fromString(
                        name: $value->op2,
                        line: $value->line,
                    ),
                ),
            ),
        ];
    }
}
