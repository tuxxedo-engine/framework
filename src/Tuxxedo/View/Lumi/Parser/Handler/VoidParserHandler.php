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
use Tuxxedo\View\Lumi\Syntax\Token\ExpressionTokenInterface;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;
use Tuxxedo\View\Lumi\Syntax\Token\VirtualTokenInterface;

class VoidParserHandler implements ParserHandlerInterface
{
    /**
     * @param class-string<TokenInterface> $tokenClassName
     */
    public function __construct(
        public readonly string $tokenClassName,
    ) {
    }

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $expectedTokenNames = [];

        foreach ($parser->handlers as $handler) {
            if (
                !\in_array($handler->tokenClassName, $expectedTokenNames, true) &&
                !\is_a($handler->tokenClassName, ExpressionTokenInterface::class, true) &&
                !\is_a($handler->tokenClassName, VirtualTokenInterface::class, true) &&
                $handler->tokenClassName !== $this->tokenClassName
            ) {
                $expectedTokenNames[] = $handler->tokenClassName;
            }
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $this->tokenClassName,
            expectedTokenNames: $expectedTokenNames,
            line: $stream->current()->line,
        );
    }
}
