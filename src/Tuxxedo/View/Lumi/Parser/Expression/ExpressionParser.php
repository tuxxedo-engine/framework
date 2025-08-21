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

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;

class ExpressionParser implements ExpressionParserInterface
{
    public function parse(
        TokenStreamInterface $stream,
    ): ExpressionNodeInterface {
        // @todo Reorganize this to handle all expression grammar
        $token = $stream->current();

        if ($token->type !== BuiltinTokenNames::VARIABLE->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $token->type,
                expectedTokenName: BuiltinTokenNames::VARIABLE->name,
            );
        } elseif ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $variable = new IdentifierNode(
            name: $token->op1,
        );

        $stream->consume();

        if (!$stream->eof()) {
            throw ParserException::fromUnexpectedToken(
                tokenName: $stream->current()->type,
            );
        }

        return $variable;
    }
}
