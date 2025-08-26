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
use Tuxxedo\View\Lumi\Compiler\Provider\CompilerProviderInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\ExpressionCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\NodeCompilerHandler;
use Tuxxedo\View\Lumi\Compiler\Provider\TextCompilerProvider;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class Compiler implements CompilerInterface
{
    /**
     * @var array<class-string<NodeInterface>, NodeCompilerHandler>
     */
    private readonly array $handlers;

    /**
     * @param CompilerProviderInterface[] $providers
     */
    final private function __construct(
        array $providers,
        public readonly ExpressionCompilerInterface $expressionCompiler,
    ) {
        $compilerHandlers = [];

        foreach ($providers as $provider) {
            foreach ($provider->augment() as $handler) {
                $compilerHandlers[$handler->nodeClassName] = $handler;
            }
        }

        $this->handlers = $compilerHandlers;
    }

    /**
     * @return CompilerProviderInterface[]
     */
    public static function getDefaults(): array
    {
        return [
            new ExpressionCompilerProvider(),
            new TextCompilerProvider(),
        ];
    }

    public static function getDefaultExpressionCompiler(): ExpressionCompilerInterface
    {
        return new ExpressionCompiler();
    }

    /**
     * @param CompilerProviderInterface[] $providers
     */
    public static function createWithDefaultProviders(
        array $providers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
    ): static {
        return new static(
            providers: \array_merge(
                self::getDefaults(),
                $providers,
            ),
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
        );
    }

    /**
     * @param CompilerProviderInterface[] $providers
     */
    public static function createWithoutDefaultProviders(
        array $providers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
    ): static {
        return new static(
            providers: $providers,
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
        );
    }

    public function compile(
        NodeStreamInterface $stream,
    ): string {
        $source = '';

        while (!$stream->eof()) {
            $node = $stream->current();

            $stream->consume();

            $source .= $this->compileNode($node);
        }

        return $source;
    }

    public function compileNode(
        NodeInterface $node,
    ): string {
        if (!\array_key_exists($node::class, $this->handlers)) {
            throw CompilerException::fromUnexpectedNode(
                nodeClass: $node::class,
            );
        }

        return ($this->handlers[$node::class]->handler)($node, $this);
    }
}
