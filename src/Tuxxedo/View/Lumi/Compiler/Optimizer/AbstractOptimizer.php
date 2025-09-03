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

namespace Tuxxedo\View\Lumi\Compiler\Optimizer;

use Tuxxedo\View\Lumi\Compiler\CompilerDirectives;
use Tuxxedo\View\Lumi\Compiler\CompilerDirectivesInterface;
use Tuxxedo\View\Lumi\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;

abstract class AbstractOptimizer implements CompilerOptimizerInterface
{
    protected private(set) CompilerDirectivesInterface&DirectivesInterface $directives;

    public function __construct()
    {
        $this->directives = new CompilerDirectives(
            directives: DefaultDirectives::defaults(),
        );
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

    public function optimize(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $stream = static::optimizer($stream);

        $this->directives = new CompilerDirectives(
            directives: DefaultDirectives::defaults(),
        );

        return $stream;
    }
}
