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
use Tuxxedo\View\Lumi\Compiler\Optimizer\CompilerOptimizerInterface;
use Tuxxedo\View\Lumi\Compiler\Optimizer\Dce\DceCompilerOptimizer;
use Tuxxedo\View\Lumi\Compiler\Optimizer\Sccp\SccpCompilerOptimizer;
use Tuxxedo\View\Lumi\Compiler\Provider\CompilerProviderInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\ConditionalCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\ExpressionCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\LoopCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\NodeCompilerHandler;
use Tuxxedo\View\Lumi\Compiler\Provider\TextCompilerProvider;
use Tuxxedo\View\Lumi\Node\BuiltinNodeKinds;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

readonly class Compiler implements CompilerInterface
{
    /**
     * @var array<class-string<NodeInterface>, NodeCompilerHandler>
     */
    private array $handlers;

    /**
     * @param CompilerProviderInterface[] $providers
     * @param CompilerOptimizerInterface[] $optimizers
     */
    final private function __construct(
        array $providers,
        private array $optimizers,
        public ExpressionCompilerInterface $expressionCompiler,
        public CompilerStateInterface $state,
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
    public static function getDefaultProviders(): array
    {
        return [
            new ExpressionCompilerProvider(),
            new TextCompilerProvider(),
            new ConditionalCompilerProvider(),
            new LoopCompilerProvider(),
        ];
    }

    public static function getDefaultExpressionCompiler(): ExpressionCompilerInterface
    {
        return new ExpressionCompiler();
    }

    public static function getDefaultCompilerState(): CompilerStateInterface
    {
        return new CompilerState();
    }

    /**
     * @return CompilerOptimizerInterface[]
     */
    public static function getDefaultCompilerOptimizers(): array
    {
        return [
            new SccpCompilerOptimizer(),
            new DceCompilerOptimizer(),
        ];
    }

    /**
     * @param CompilerProviderInterface[] $providers
     * @param CompilerOptimizerInterface[]|null $optimizers
     */
    public static function createWithDefaultProviders(
        array $providers = [],
        ?array $optimizers = null,
        ?ExpressionCompilerInterface $expressionCompiler = null,
        ?CompilerStateInterface $state = null,
    ): static {
        return new static(
            providers: \array_merge(
                self::getDefaultProviders(),
                $providers,
            ),
            optimizers: $optimizers ?? self::getDefaultCompilerOptimizers(),
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
            state: $state ?? self::getDefaultCompilerState(),
        );
    }

    /**
     * @param CompilerProviderInterface[] $providers
     * @param CompilerOptimizerInterface[]|null $optimizers
     */
    public static function createWithoutDefaultProviders(
        array $providers = [],
        ?array $optimizers = null,
        ?ExpressionCompilerInterface $expressionCompiler = null,
        ?CompilerStateInterface $state = null,
    ): static {
        return new static(
            providers: $providers,
            optimizers: $optimizers ?? self::getDefaultCompilerOptimizers(),
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
            state: $state ?? self::getDefaultCompilerState(),
        );
    }

    public function compile(
        NodeStreamInterface $stream,
    ): string {
        if (\sizeof($this->optimizers) > 0) {
            foreach ($this->optimizers as $optimizer) {
                $stream = $optimizer->optimize($stream);
            }
        }

        $source = '';

        $this->state->enter(BuiltinNodeKinds::ROOT->name);

        while (!$stream->eof()) {
            $node = $stream->current();

            $stream->consume();

            $source .= $this->compileNode($node);
        }

        $this->state->leave(BuiltinNodeKinds::ROOT->name);

        return $source;
    }

    public function compileNode(
        NodeInterface $node,
    ): string {
        if (!\array_key_exists($node::class, $this->handlers)) {
            throw CompilerException::fromUnexpectedNode(
                nodeClass: $node::class,
            );
        } elseif (!$this->state->valid($node)) {
            throw CompilerException::fromUnexpectedState(
                kind: $node->kind,
                expects: $this->state->expects ?? '',
            );
        }

        return ($this->handlers[$node::class]->handler)($node, $this);
    }
}
