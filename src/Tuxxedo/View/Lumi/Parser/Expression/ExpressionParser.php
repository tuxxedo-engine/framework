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
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserStateInterface;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class ExpressionParser implements ExpressionParserInterface
{
    public function parse(
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        throw new \Exception('Not implemented');
    }

    // @todo Fix visibility
    public function validateToken(TokenInterface $token): void
    {
        match ($token->type) {
            BuiltinTokenNames::CHARACTER->name, BuiltinTokenNames::OPERATOR->name, BuiltinTokenNames::VARIABLE->name => $token->op1 !== null
                ? null
                : throw ParserException::fromMalformedToken(),
            BuiltinTokenNames::TYPE->name => ($token->op1 !== null && $token->op2 !== null)
                ? null
                : throw ParserException::fromMalformedToken(),
            default => throw ParserException::fromUnexpectedToken(
                tokenName: $token->type,
            ),
        };
    }
}
