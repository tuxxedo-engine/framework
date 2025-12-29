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

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompiler;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\CompilerProviderInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\ConditionalCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\ExpressionCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\LoopCompilerProvider;
use Tuxxedo\View\Lumi\Compiler\Provider\NodeCompilerHandlerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\PostNodeCompilerHandlerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\StagedNodeCompilerHandler;
use Tuxxedo\View\Lumi\Compiler\Provider\StagedNodeCompilerHandlerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\TextCompilerProvider;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

class Compiler implements CompilerInterface
{
    /**
     * @var array<class-string<NodeInterface>, NodeCompilerHandlerInterface>
     */
    private readonly array $handlers;

    /**
     * @var array<class-string<NodeInterface>, PostNodeCompilerHandlerInterface>
     */
    private readonly array $postHandlers;

    /**
     * @var StagedNodeCompilerHandlerInterface[]
     */
    private array $stagedPostHandlers = [];

    /**
     * @param CompilerProviderInterface[] $providers
     */
    final private function __construct(
        array $providers,
        public readonly ExpressionCompilerInterface $expressionCompiler,
        public readonly CompilerStateInterface $state,
        public readonly EscaperInterface $escaper,
    ) {
        $compilerHandlers = [];
        $postCompilerHandlers = [];

        foreach ($providers as $provider) {
            foreach ($provider->augment() as $handler) {
                if ($handler instanceof PostNodeCompilerHandlerInterface) {
                    $postCompilerHandlers[$handler->nodeClassName] = $handler;
                } else {
                    $compilerHandlers[$handler->nodeClassName] = $handler;
                }
            }
        }

        $this->handlers = $compilerHandlers;
        $this->postHandlers = $postCompilerHandlers;
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

    public static function getDefaultEscaper(): EscaperInterface
    {
        return new Escaper();
    }

    /**
     * @param CompilerProviderInterface[] $providers
     */
    public static function createWithDefaultProviders(
        array $providers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
        ?CompilerStateInterface $state = null,
        ?EscaperInterface $escaper = null,
    ): static {
        return new static(
            providers: \array_merge(
                self::getDefaultProviders(),
                $providers,
            ),
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
            state: $state ?? self::getDefaultCompilerState(),
            escaper: $escaper ?? self::getDefaultEscaper(),
        );
    }

    /**
     * @param CompilerProviderInterface[] $providers
     */
    public static function createWithoutDefaultProviders(
        array $providers = [],
        ?ExpressionCompilerInterface $expressionCompiler = null,
        ?CompilerStateInterface $state = null,
        ?EscaperInterface $escaper = null,
    ): static {
        return new static(
            providers: $providers,
            expressionCompiler: $expressionCompiler ?? self::getDefaultExpressionCompiler(),
            state: $state ?? self::getDefaultCompilerState(),
            escaper: $escaper ?? self::getDefaultEscaper(),
        );
    }

    public function compile(
        NodeStreamInterface $stream,
    ): string {
        $source = '';

        $this->state->enter(NodeScope::STATEMENT);

        while (!$stream->eof()) {
            $source .= $this->compileNode($stream->consume(), $stream);
        }

        $this->state->leave(NodeScope::STATEMENT);

        if ($source !== '') {
            $source = '<?php declare(strict_types=1); ?>' . $source;

            foreach ($this->stagedPostHandlers as $handler) {
                $source .= ($handler->handler)($handler->node, $this, $stream);
            }
        }

        $this->stagedPostHandlers = [];

        return $source;
    }

    public function compileNode(
        NodeInterface $node,
        NodeStreamInterface $stream,
    ): string {
        if (!\array_key_exists($node::class, $this->handlers)) {
            if (\array_key_exists($node::class, $this->postHandlers)) {
                $this->stagedPostHandlers[] = new StagedNodeCompilerHandler(
                    node: $node,
                    handler: ($this->postHandlers[$node::class]->handler)(...),
                );

                return '';
            }

            throw CompilerException::fromUnexpectedNode(
                nodeClass: $node::class,
            );
        } elseif (!$this->state->valid($node)) {
            throw CompilerException::fromUnexpectedState(
                scopes: $node->scopes,
                expects: $this->state->expects,
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
