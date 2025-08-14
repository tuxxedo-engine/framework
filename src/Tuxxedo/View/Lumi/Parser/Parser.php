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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Lexer\Token\TokenInterface;
use Tuxxedo\View\Lumi\Parser\Handler\ParserHandlerInterface;
use Tuxxedo\View\Lumi\Parser\Node\NodeInterface;

class Parser implements ParserInterface
{
    /**
     * @var ParserHandlerInterface[]
     */
    private readonly array $handlers;

    /**
     * @param ParserHandlerInterface[] $handlers
     *
     * @throws ParserException
     */
    final private function __construct(
        array $handlers = [],
    ) {
        $parserHandlers = [];

        foreach ($handlers as $handler) {
            $parserHandlers[$handler->tokenType->name] = $handler;
        }

        $this->handlers = $parserHandlers;
    }

    /**
     * @return ParserHandlerInterface[]
     */
    private static function getDefaults(): array
    {
        return [
            // @todo Fill in
        ];
    }

    /**
     * @param ParserHandlerInterface[] $handlers
     */
    public static function createWithDefaultHandlers(
        array $handlers = [],
    ): static {
        return new static(
            handlers: \array_merge(
                self::getDefaults(),
                $handlers,
            ),
        );
    }

    /**
     * @param ParserHandlerInterface[] $handlers
     */
    public static function createWithoutDefaultHandlers(
        array $handlers = [],
    ): static {
        return new static(
            handlers: $handlers,
        );
    }

    /**
     * @param TokenInterface[] $tokens
     * @return NodeInterface[]
     *
     * @throws ParserException
     */
    public function parse(array $tokens): array
    {
        $nodes = [];

        foreach ($tokens as $token) {
            if (!\array_key_exists($token->type->name, $this->handlers)) {
                throw ParserException::fromUnknownToken(
                    tokenType: $token->type,
                );
            }

            $nodes = \array_merge(
                $nodes,
                $this->handlers[$token->type->name]->parse($token),
            );
        }

        return $nodes;
    }
}
