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

namespace Tuxxedo\View\Lumi\Optimizer\Dce;

use Tuxxedo\View\Lumi\Optimizer\AbstractOptimizer;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class DceOptimizer extends AbstractOptimizer
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
            $node instanceof AssignmentNode => parent::assignment($node),
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof BlockNode => parent::optimizeBlock($node),
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
            $ifEvaluation = $this->evaluate($node->operand);

            if ($ifEvaluation === DceEvaluateResult::CANNOT_DETERMINE) {
                return [
                    $node,
                ];
            }

            if ($ifEvaluation === DceEvaluateResult::ALWAYS_TRUE) {
                return parent::optimizeNodes($node->body);
            }

            $newElse = null;
            $eliminationBranches = [];

            foreach ($node->branches as $index => $branch) {
                $evaluation = $this->evaluate($branch->operand);

                if ($evaluation === DceEvaluateResult::ALWAYS_FALSE) {
                    $eliminationBranches[$index] = true;
                } elseif ($evaluation === DceEvaluateResult::ALWAYS_TRUE) {
                    $newElse = $index;

                    break;
                } else {
                    $eliminationBranches[$index] = false;
                }
            }

            $newIf = \array_search(true, $eliminationBranches, true);

            if ($newIf === false) {
                return parent::optimizeNodes($node->else);
            }

            $branches = [];

            foreach ($eliminationBranches as $index => $branch) {
                if ($index === $newIf) {
                    continue;
                } elseif ($index === $newElse) {
                    break;
                }

                if ($branch === false) {
                    $branches[] = new ConditionalBranchNode(
                        operand: $node->branches[$index]->operand,
                        body: parent::optimizeNodes($node->branches[$index]->body),
                    );
                }
            }

            return [
                new ConditionalNode(
                    operand: $node->branches[$newIf]->operand,
                    body: parent::optimizeNodes($node->branches[$newIf]->body),
                    branches: $branches,
                    else: $newElse !== null
                        ? parent::optimizeNodes($node->branches[$newElse]->body)
                        : parent::optimizeNodes($node->else),
                ),
            ];
        }

        $evaluates = $this->evaluate($node->operand);

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

    private function evaluate(
        ExpressionNodeInterface $node,
    ): DceEvaluateResult {
        return match (true) {
            $node instanceof LiteralNode => $this->evaluateLiteral($node),
            $node instanceof IdentifierNode => $this->evaluateIdentifier($node),
            default => DceEvaluateResult::CANNOT_DETERMINE,
        };
    }

    private function evaluateLiteral(
        LiteralNode $node,
    ): DceEvaluateResult {
        return match ($node->type) {
            NativeType::NULL => DceEvaluateResult::ALWAYS_FALSE,
            default => DceEvaluateResult::fromBool(\boolval($node->type->cast($node->operand))),
        };
    }

    private function evaluateIdentifier(
        IdentifierNode $node,
    ): DceEvaluateResult {
        $value = $this->scope->get($node->name)->value;

        if ($value instanceof LiteralNode) {
            return $this->evaluateLiteral($value);
        }

        return DceEvaluateResult::CANNOT_DETERMINE;
    }
}
