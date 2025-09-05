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
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class VoidParserHandler implements ParserHandlerInterface
{
    public function __construct(
        public readonly string $tokenName,
    ) {
    }

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $expectedTokenNames = [];

        foreach (BuiltinTokenNames::cases() as $builtinTokenName) {
            if (
                !$builtinTokenName->isExpressionToken() &&
                !$builtinTokenName->isVirtualToken()
            ) {
                $expectedTokenNames[] = $builtinTokenName->name;
            }
        }

        foreach ($parser->handlers as $handler) {
            if (
                !\in_array($handler->tokenName, $expectedTokenNames, true) &&
                $handler->tokenName !== $this->tokenName
            ) {
                $expectedTokenNames[] = $handler->tokenName;
            }
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $this->tokenName,
            expectedTokenNames: $expectedTokenNames,
        );
    }
}
