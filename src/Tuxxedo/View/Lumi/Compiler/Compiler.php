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

namespace Tuxxedo\View\Lumi\Compiler;

use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompiler;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Handler\CompilerHandlerInterface;
use Tuxxedo\View\Lumi\Compiler\Handler\EchoCompilerHandler;
use Tuxxedo\View\Lumi\Compiler\Handler\TextCompilerHandler;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class Compiler implements CompilerInterface
{
    /**
     * @var array<class-string<NodeInterface>, CompilerHandlerInterface>
     */
    private readonly array $handlers;

    /**
     * @param CompilerHandlerInterface[] $handlers
     */
    final private function __construct(
        array $handlers,
        private readonly ExpressionCompilerInterface $expressionCompiler,
    ) {
        $compilerHandlers = [];

        foreach ($handlers as $handler) {
            $compilerHandlers[$handler->getRootNodeClass()] = $handler;
        }

        $this->handlers = $compilerHandlers;
    }

    /**
     * @return CompilerHandlerInterface[]
     */
    public static function getDefaults(): array
    {
        return [
            new TextCompilerHandler(),
            new EchoCompilerHandler(),
        ];
    }

    public static function getDefaultExpressionCompiler(): ExpressionCompilerInterface
    {
        return new ExpressionCompiler();
    }

    /**
     * @param CompilerHandlerInterface[] $handlers
     */
    public static function createWithDefaultHandlers(
        array $handlers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
    ): static {
        return new static(
            handlers: \array_merge(
                self::getDefaults(),
                $handlers,
            ),
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
        );
    }

    /**
     * @param CompilerHandlerInterface[] $handlers
     */
    public static function createWithoutDefaultHandlers(
        array $handlers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
    ): static {
        return new static(
            handlers: $handlers,
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
        );
    }

    public function compile(
        NodeStreamInterface $stream,
    ): string {
        $source = '';

        while (!$stream->eof()) {
            $node = $stream->current();

            if (!\array_key_exists($node::class, $this->handlers)) {
                throw CompilerException::fromUnexpectedNode(
                    nodeClass: $node::class,
                );
            }

            $stream->consume();

            $source .= $this->handlers[$node::class]->compile($node, $this->expressionCompiler);
        }

        return $source;
    }
}
