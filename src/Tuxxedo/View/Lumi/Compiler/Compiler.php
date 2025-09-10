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
use Tuxxedo\View\Lumi\Compiler\Provider\ConditionalCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\ExpressionCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\LoopCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\NodeCompilerHandler;
use Tuxxedo\View\Lumi\Compiler\Provider\TextCompilerProvider;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\BuiltinNodeKinds;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

readonly class Compiler implements CompilerInterface
{
    /**
     * @var array<class-string<NodeInterface>, NodeCompilerHandler>
     */
    private array $handlers;

    /**
     * @param CompilerProviderInterface[] $providers
     */
    final private function __construct(
        array $providers,
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
     * @param CompilerProviderInterface[] $providers
     */
    public static function createWithDefaultProviders(
        array $providers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
        ?CompilerStateInterface $state = null,
    ): static {
        return new static(
            providers: \array_merge(
                self::getDefaultProviders(),
                $providers,
            ),
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
            state: $state ?? self::getDefaultCompilerState(),
        );
    }

    /**
     * @param CompilerProviderInterface[] $providers
     */
    public static function createWithoutDefaultProviders(
        array $providers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
        ?CompilerStateInterface $state = null,
    ): static {
        return new static(
            providers: $providers,
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
            state: $state ?? self::getDefaultCompilerState(),
        );
    }

    public function compile(
        NodeStreamInterface $stream,
    ): string {
        $source = '';

        $this->state->enter(BuiltinNodeKinds::ROOT->name);

        while (!$stream->eof()) {
            $node = $stream->current();

            $stream->consume();

            $source .= $this->compileNode($node, $stream);
        }

        $this->state->leave(BuiltinNodeKinds::ROOT->name);

        if ($source !== '') {
            $source = "<?php declare(strict_types=1); ?>\n" . $source;
        }

        return $source;
    }

    public function compileNode(
        NodeInterface $node,
        NodeStreamInterface $stream,
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

        return ($this->handlers[$node::class]->handler)($node, $this, $stream);
    }

    public function compileExpression(
        ExpressionNodeInterface $node,
    ): string {
        return $this->expressionCompiler->compile(
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
            compiler: $this,
        );
    }
}
