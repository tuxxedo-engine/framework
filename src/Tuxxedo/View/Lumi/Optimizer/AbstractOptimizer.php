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

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\Compiler\CompilerDirectives;
use Tuxxedo\View\Lumi\Compiler\CompilerDirectivesInterface;
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

abstract class AbstractOptimizer implements OptimizerInterface
{
    protected private(set) CompilerDirectivesInterface&DirectivesInterface $directives;
    protected private(set) ScopeInterface $scope;
    protected private(set) bool $layoutMode = false;

    /**
     * @var ScopeInterface[]
     */
    protected private(set) array $scopeStack = [];

    public function __construct()
    {
        $this->directives = CompilerDirectives::createWithDefaults();
        $this->scope = new Scope();
    }

    abstract protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface;

    protected function pushScope(): void
    {
        \array_push($this->scopeStack, $this->scope);

        $this->scope = new Scope();
    }

    /**
     * @throws CompilerException
     */
    protected function popScope(): void
    {
        $scope = \array_pop($this->scopeStack);

        if ($scope === null) {
            throw CompilerException::fromCannotPopOptimizerScope();
        }

        $this->scope = $scope;
    }

    /**
     * @return array{0: NodeInterface}
     */
    protected function assignment(
        AssignmentNode $node,
    ): array {
        $this->scope->assign($node);

        return [
            $node,
        ];
    }

    /**
     * @return array{0: NodeInterface}
     */
    protected function optimizeDirective(
        DirectiveNodeInterface $node,
    ): array {
        $this->directives->set(
            $node->directive->operand,
            match ($node->value->type) {
                NativeType::STRING => $node->value->operand,
                NativeType::INT => \intval($node->value->operand),
                NativeType::FLOAT => \floatval($node->value->operand),
                NativeType::BOOL => $node->value->operand === 'true',
                NativeType::NULL => null,
            },
        );

        return [
            $node,
        ];
    }

    /**
     * @return array{0: BlockNode}
     *
     * @throws CompilerException
     */
    protected function optimizeBlock(
        BlockNode $node,
    ): array {
        $this->pushScope();

        $nodes = $this->optimizeNodes($node->body);

        $this->popScope();

        return [
            new BlockNode(
                name: $node->name,
                body: $nodes,
            ),
        ];
    }

    /**
     * @param NodeInterface[] $nodes
     * @return NodeInterface[]
     */
    protected function optimizeNodes(
        array $nodes,
    ): array {
        if (\sizeof($nodes) === 0) {
            return $nodes;
        }

        return $this->optimizer(
            stream: new NodeStream(
                nodes: $nodes,
            ),
        )->nodes;
    }

    protected function isLayoutStream(
        NodeStreamInterface $stream,
    ): bool {
        foreach ($stream->nodes as $node) {
            if ($node instanceof LayoutNode) {
                return true;
            }
        }

        return false;
    }

    public function optimize(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $this->layoutMode = $this->isLayoutStream($stream);

        $stream = static::optimizer($stream);

        $this->directives = CompilerDirectives::createWithDefaults();
        $this->layoutMode = false;

        return $stream;
    }
}
