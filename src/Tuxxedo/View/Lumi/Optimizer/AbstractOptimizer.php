<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectives;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectivesInterface;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorInterface;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorResult;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\LumiNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Type;

abstract class AbstractOptimizer implements OptimizerInterface
{
    protected private(set) MutableDirectivesInterface&DirectivesInterface $directives;
    protected private(set) OptimizerContext $context;
    protected private(set) ScopeInterface $scope;

    /**
     * @var OptimizerContext[]
     */
    protected private(set) array $contextStack = [];

    /**
     * @var ScopeInterface[]
     */
    protected private(set) array $scopeStack = [];

    public function __construct(
        protected readonly EvaluatorInterface $evaluator = new Evaluator(),
    ) {
        $this->blankState();
    }

    private function blankState(
        ?DirectivesInterface $directives = null,
    ): void {
        $this->directives = $directives !== null
            ? new MutableDirectives($directives->directives)
            : MutableDirectives::createWithDefaults();

        $this->context = OptimizerContext::NONE;
        $this->scope = new Scope(
            evaluator: $this->evaluator,
        );
    }

    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];
        $eatLeadingNewline = false;

        while (!$stream->eof()) {
            $node = $stream->consume();

            if ($eatLeadingNewline && $node instanceof TextNode) {
                $eatLeadingNewline = false;
                $stripped = self::withoutLeadingNewline(
                    nodes: [
                        $node,
                    ],
                );

                if (\sizeof($stripped) === 0) {
                    continue;
                }

                $node = $stripped[0];
            }

            $this->markMutatedAsVarying(
                nodes: [
                    $node,
                ],
                assignments: false,
            );

            $optimizedNodes = $this->optimizeNode($stream, $node);

            $eatLeadingNewline = self::closesWithPhpTag($node) &&
                !self::closesWithPhpTag($optimizedNodes[\array_key_last($optimizedNodes) ?? -1] ?? null);

            if (\sizeof($optimizedNodes) > 0) {
                \array_push($nodes, ...$optimizedNodes);
            }
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    /**
     * @param NodeInterface[] $nodes
     * @return NodeInterface[]
     */
    protected static function withoutLeadingNewline(
        array $nodes,
    ): array {
        $nodes = \array_values($nodes);

        if (!isset($nodes[0]) || !$nodes[0] instanceof TextNode) {
            return $nodes;
        }

        $text = (string) \preg_replace('/^\r?\n/', '', $nodes[0]->text);

        if ($text === $nodes[0]->text) {
            return $nodes;
        }

        if ($text === '') {
            return \array_slice($nodes, 1);
        }

        $nodes[0] = new TextNode(
            text: $text,
            context: $nodes[0]->context,
        );

        return $nodes;
    }

    private static function closesWithPhpTag(
        ?NodeInterface $node,
    ): bool {
        return $node !== null &&
            !$node instanceof TextNode &&
            !$node instanceof CommentNode &&
            !$node instanceof LayoutNode &&
            !$node instanceof LumiNode;
    }

    /**
     * @param NodeInterface[] $nodes
     */
    private function markMutatedAsVarying(
        array $nodes,
        bool $assignments,
    ): void {
        foreach ($nodes as $node) {
            if (
                ($assignments && $node instanceof AssignmentNode) ||
                (
                    $node instanceof UnaryOpNode &&
                    \in_array(
                        $node->operator,
                        [
                            UnarySymbol::INCREMENT_PRE,
                            UnarySymbol::INCREMENT_POST,
                            UnarySymbol::DECREMENT_PRE,
                            UnarySymbol::DECREMENT_POST,
                        ],
                        true,
                    )
                )
            ) {
                $target = $node instanceof AssignmentNode
                    ? $node->name
                    : $node->operand;

                while ($target instanceof PropertyAccessNode || $target instanceof ArrayAccessNode) {
                    $target = $target instanceof PropertyAccessNode
                        ? $target->accessor
                        : $target->array;
                }

                if ($target instanceof IdentifierNode) {
                    $this->scope->create($target);
                }
            }

            $children = [];

            foreach (\get_object_vars($node) as $property) {
                foreach (\is_array($property) ? $property : [$property] as $child) {
                    if ($child instanceof NodeInterface) {
                        $children[] = $child;
                    }
                }
            }

            $this->markMutatedAsVarying(
                nodes: $children,
                assignments: $assignments,
            );
        }
    }

    /**
     * @param NodeInterface[] $nodes
     * @return NodeInterface[]
     */
    protected function optimizeNodes(
        array $nodes,
        OptimizerContext $context,
    ): array {
        if (\sizeof($nodes) === 0) {
            return $nodes;
        }

        try {
            $this->pushContext($context);

            return $this->optimizer(
                stream: new NodeStream(
                    nodes: $nodes,
                ),
            )->nodes;
        } finally {
            $this->popContext();
        }
    }

    public function optimize(
        NodeStreamInterface $stream,
        DirectivesInterface $directives,
    ): OptimizerResultInterface {
        $this->blankState($directives);

        $oldStream = clone $stream;
        $stream = static::optimizer($stream);

        return OptimizerResult::create(
            oldStream: $oldStream,
            newStream: $stream,
        );
    }

    /**
     * @return NodeInterface[]
     */
    abstract protected function optimizeNode(
        NodeStreamInterface $stream,
        NodeInterface $node,
        ?OptimizerContext $context = null,
    ): array;

    protected function isInContext(
        OptimizerContext $context,
    ): bool {
        return $this->context === $context || \in_array($context, $this->contextStack, true);
    }

    protected function isInOneOfContext(
        OptimizerContext ...$context,
    ): bool {
        foreach ($context as $specificContext) {
            if ($this->isInContext($specificContext)) {
                return true;
            }
        }

        return false;
    }

    protected function pushContext(
        OptimizerContext $context,
    ): void {
        \array_push($this->contextStack, $this->context);

        $this->context = $context;
    }

    /**
     * @throws OptimizerException
     */
    protected function popContext(): void
    {
        $context = \array_pop($this->contextStack);

        if ($context === null) {
            throw OptimizerException::fromCannotPopOptimizerContext();
        }

        $this->context = $context;
    }

    protected function pushScope(
        bool $inherit = false,
    ): void {
        if ($inherit) {
            $newScope = clone $this->scope;

            \array_push($this->scopeStack, $this->scope);

            $this->scope = $newScope;
        } else {
            \array_push($this->scopeStack, $this->scope);

            $this->scope = new Scope(
                evaluator: $this->evaluator,
            );
        }
    }

    /**
     * @throws OptimizerException
     */
    protected function popScope(
        bool $merge = false,
    ): void {
        $scope = \array_pop($this->scopeStack);

        if ($scope === null) {
            throw OptimizerException::fromCannotPopOptimizerScope();
        }

        if ($merge) {
            $scope = $scope->merge($this->scope);
        }

        $this->scope = $scope;
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
                Type::STRING => $node->value->operand,
                Type::INT => \intval($node->value->operand),
                Type::FLOAT => \floatval($node->value->operand),
                Type::BOOL => \strtolower($node->value->operand) === 'true',
                Type::NULL => null,
            },
        );

        return [
            $node,
        ];
    }

    /**
     * @param array<ExpressionNodeInterface|null> $conditions
     * @param array<NodeInterface[]> $bodies
     * @return array<NodeInterface[]>
     */
    protected function optimizeConditionalBodies(
        array $conditions,
        array $bodies,
    ): array {
        $entry = $this->scope;
        $reachable = [];
        $taken = false;

        foreach ($conditions as $index => $condition) {
            $evaluation = $condition !== null
                ? $this->evaluator->checkExpression($entry, $condition)
                : EvaluatorResult::IS_TRUE;

            $reachable[$index] = !$taken && $evaluation !== EvaluatorResult::IS_FALSE;
            $taken = $taken || $evaluation === EvaluatorResult::IS_TRUE;
        }

        $optimized = [];
        $paths = [];

        try {
            foreach ($bodies as $index => $body) {
                $this->scope = clone $entry;

                $optimized[$index] = $this->optimizeNodes(
                    nodes: $body,
                    context: OptimizerContext::CONDITION,
                );

                if ($reachable[$index] ?? true) {
                    $paths[] = $this->scope;
                }
            }
        } finally {
            $this->scope = $entry;
        }

        $entry->join(...$paths);

        return $optimized;
    }

    /**
     * @throws OptimizerException
     */
    protected function optimizeBlockBody(
        BlockNode $node,
    ): BlockNode {
        $this->pushScope();

        $nodes = $this->optimizeNodes(
            nodes: $node->body,
            context: OptimizerContext::BLOCK,
        );

        $this->popScope();

        return new BlockNode(
            name: $node->name,
            body: $nodes,
        );
    }

    /**
     * @throws OptimizerException
     */
    protected function optimizeForBody(
        ForNode $node,
    ): ForNode {
        $this->markMutatedAsVarying(
            nodes: $node->body,
            assignments: true,
        );

        $this->scope->create($node->value);

        if ($node->key !== null) {
            $this->scope->create($node->key);
        }

        $this->pushScope(inherit: true);

        $nodes = $this->optimizeNodes(
            nodes: $node->body,
            context: OptimizerContext::FOR,
        );

        $this->popScope(merge: true);

        return new ForNode(
            value: $node->value,
            iterator: $node->iterator,
            body: $nodes,
            key: $node->key,
        );
    }

    /**
     * @throws OptimizerException
     */
    protected function optimizeDoWhileBody(
        DoWhileNode $node,
    ): DoWhileNode {
        $this->markMutatedAsVarying(
            nodes: $node->body,
            assignments: true,
        );

        $this->pushScope(inherit: true);

        $nodes = $this->optimizeNodes(
            nodes: $node->body,
            context: OptimizerContext::DO_WHILE,
        );

        $this->popScope(merge: true);

        return new DoWhileNode(
            operand: $node->operand,
            body: $nodes,
        );
    }

    /**
     * @throws OptimizerException
     */
    protected function optimizeWhileBody(
        WhileNode $node,
    ): WhileNode {
        $this->markMutatedAsVarying(
            nodes: $node->body,
            assignments: true,
        );

        $this->pushScope(inherit: true);

        $nodes = $this->optimizeNodes(
            nodes: $node->body,
            context: OptimizerContext::WHILE,
        );

        $this->popScope(merge: true);

        return new WhileNode(
            operand: $node->operand,
            body: $nodes,
        );
    }

    /**
     * @return TextNode[]
     */
    protected function optimizeText(
        NodeStreamInterface $stream,
        TextNode $node,
    ): array {
        if ($node->context !== TextContext::NONE) {
            return [
                $node,
            ];
        }

        $text = $node->text;

        while (!$stream->eof()) {
            $nextNode = $stream->current();

            if (
                !$nextNode instanceof TextNode ||
                $nextNode->context !== TextContext::NONE
            ) {
                break;
            }

            $text .= $nextNode->text;

            $stream->consume();
        }

        if ($text !== $node->text) {
            return [
                new TextNode(
                    text: $text,
                    context: TextContext::NONE,
                ),
            ];
        }

        return [
            $node,
        ];
    }
}
