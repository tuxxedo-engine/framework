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
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentOperator;

abstract class AbstractOptimizer implements OptimizerInterface
{
    protected private(set) CompilerDirectivesInterface&DirectivesInterface $directives;

    /**
     * @var array<string, VariableInterface>
     */
    // @todo This may need to be passed into each variables mutation scope
    protected private(set) array $variables = [];

    public function __construct()
    {
        $this->directives = CompilerDirectives::createWithDefaults();
    }

    abstract protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface;

    /**
     * @return array{0: NodeInterface}
     */
    protected function assignment(
        AssignmentNode $node,
    ): array {
        if (
            $node->operator !== AssignmentOperator::ASSIGN &&
            \array_key_exists($node->name->name, $this->variables)
        ) {
            $this->variables[$node->name->name]->mutate($node->value, $node->operator);
        } else {
            $this->variables[$node->name->name] = Variable::fromNode($node);
        }

        return [
            $node,
        ];
    }

    protected function variable(
        string $name,
    ): VariableInterface {
        return $this->variables[$name] ?? Variable::fromUndefined($name);
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

    public function optimize(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $stream = static::optimizer($stream);

        $this->directives = CompilerDirectives::createWithDefaults();

        return $stream;
    }
}
