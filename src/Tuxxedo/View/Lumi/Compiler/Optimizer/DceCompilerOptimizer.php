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

use Tuxxedo\View\Lumi\Node\CommentNode;
use Tuxxedo\View\Lumi\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class DceCompilerOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $optimizedNodes = $this->optimizeNode($stream->current());

            if (\sizeof($optimizedNodes) > 0) {
                \array_push($nodes, ...$optimizedNodes);
            }

            $stream->consume();
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeNode(
        NodeInterface $node,
    ): array {
        return match (true) {
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof CommentNode => $this->optimizeComment($node),
            $node instanceof ConditionalNode => $this->optimizeConditional($node),
            default => [
                $node,
            ],
        };
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeComment(
        CommentNode $node,
    ): array {
        if ($this->directives->asBool('lumi.compiler_strip_comments')) {
            return [];
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeConditional(
        ConditionalNode $node,
    ): array {
        if (\sizeof($node->branches) > 0) {
            // @todo Implement DCE rewrite for conditionals with elseif

            return [
                $node,
            ];
        }

        $evaluates = $this->evaluates($node->operand);

        if ($evaluates === DceEvaluateResult::ALWAYS_FALSE) {
            return parent::optimizeNodes($node->else);
        }

        if ($evaluates === DceEvaluateResult::ALWAYS_TRUE) {
            return parent::optimizeNodes($node->body);
        }

        return [
            $node,
        ];
    }

    private function evaluates(
        ExpressionNodeInterface $node,
    ): DceEvaluateResult {
        if (!$node instanceof LiteralNode) {
            return DceEvaluateResult::CANNOT_DETERMINE;
        }

        return match ($node->type) {
            NodeNativeType::NULL => DceEvaluateResult::ALWAYS_FALSE,
            NodeNativeType::BOOL => DceEvaluateResult::fromBool($node->operand !== 'false'),
            NodeNativeType::FLOAT => DceEvaluateResult::fromBool(\boolval(\floatval($node->operand))),
            NodeNativeType::INT => DceEvaluateResult::fromBool(\boolval(\intval($node->operand))),
            NodeNativeType::STRING => DceEvaluateResult::fromBool(\boolval($node->operand)),
        };
    }
}
