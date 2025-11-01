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
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorResult;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;

class DceOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $optimizedNodes = $this->optimizeNode($stream, $stream->consume());

            if (\sizeof($optimizedNodes) > 0) {
                \array_push($nodes, ...$optimizedNodes);
            }
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeNode(
        NodeStreamInterface $stream,
        NodeInterface $node,
    ): array {
        return match (true) {
            $node instanceof AssignmentNode => parent::assignment($node),
            $node instanceof BreakNode => $this->optimizeLoopStatement($stream),
            $node instanceof BlockNode => [
                parent::optimizeBlockBody($node),
            ],
            $node instanceof CommentNode => $this->optimizeComment($node),
            $node instanceof ConditionalNode => $this->optimizeConditional($node),
            $node instanceof ContinueNode => $this->optimizeLoopStatement($stream),
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof DoWhileNode => $this->optimizeDoWhile($node),
            $node instanceof ForNode => [
                parent::optimizeForBody($node),
            ],
            $node instanceof TextNode => $this->optimizeText($stream, $node),
            $node instanceof WhileNode => $this->optimizeWhile($node),
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
            $ifEvaluation = $this->evaluator->evaluateExpression($this->scope, $node->operand);

            if ($ifEvaluation === EvaluatorResult::UNKNOWN) {
                return [
                    $node,
                ];
            }

            if ($ifEvaluation === EvaluatorResult::IS_TRUE) {
                return parent::optimizeNodes($node->body);
            }

            $newElse = null;
            $eliminationBranches = [];

            foreach ($node->branches as $index => $branch) {
                $evaluation = $this->evaluator->evaluateExpression($this->scope, $branch->operand);

                if ($evaluation === EvaluatorResult::IS_FALSE) {
                    $eliminationBranches[$index] = true;
                } elseif ($evaluation === EvaluatorResult::IS_TRUE) {
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

        $evaluation = $this->evaluator->evaluateExpression($this->scope, $node->operand);

        if ($evaluation === EvaluatorResult::IS_FALSE) {
            return parent::optimizeNodes($node->else);
        }

        if ($evaluation === EvaluatorResult::IS_TRUE) {
            return parent::optimizeNodes($node->body);
        }

        return [
            $node,
        ];
    }

    /**
     * @return TextNode[]
     */
    protected function optimizeText(
        NodeStreamInterface $stream,
        TextNode $node,
    ): array {
        if (
            $this->layoutMode &&
            \sizeof($this->scopeStack) === 0
        ) {
            return [];
        }

        return parent::optimizeText($stream, $node);
    }

    /**
     * @return NodeInterface[]
     */
    protected function optimizeDoWhile(
        DoWhileNode $node,
    ): array {
        $node = parent::optimizeDoWhileBody($node);

        if ($this->evaluator->evaluateExpression($this->scope, $node->operand) === EvaluatorResult::IS_FALSE) {
            return $node->body;
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    protected function optimizeWhile(
        WhileNode $node,
    ): array {
        $node = parent::optimizeWhileBody($node);

        if ($this->evaluator->evaluateExpression($this->scope, $node->operand) === EvaluatorResult::IS_FALSE) {
            return $node->body;
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    protected function optimizeLoopStatement(
        NodeStreamInterface $stream,
    ): array {
        while (!$stream->eof()) {
            $stream->consume();
        }

        return [];
    }
}
