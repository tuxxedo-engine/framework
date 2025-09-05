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
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeNativeType;

abstract class AbstractOptimizer implements OptimizerInterface
{
    protected private(set) CompilerDirectivesInterface&DirectivesInterface $directives;

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
    protected function optimizeDirective(
        DirectiveNodeInterface $node,
    ): array {
        $this->directives->set(
            $node->directive->operand,
            match ($node->value->type) {
                NodeNativeType::STRING => $node->value->operand,
                NodeNativeType::INT => \intval($node->value->operand),
                NodeNativeType::FLOAT => \floatval($node->value->operand),
                NodeNativeType::BOOL => $node->value->operand === 'true',
                NodeNativeType::NULL => null,
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
