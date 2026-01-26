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
use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorInterface;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Type;

// @todo Bodies of statements and loops needs more advanced computation for value mutation checks if part of a condition
abstract class AbstractOptimizer implements OptimizerInterface
{
    protected readonly EvaluatorInterface $evaluator;
    protected private(set) CompilerDirectivesInterface&DirectivesInterface $directives;
    protected private(set) ScopeInterface $scope;

    /**
     * @var ScopeInterface[]
     */
    protected private(set) array $scopeStack = [];

    public function __construct(
        ?EvaluatorInterface $evaluator = null,
    ) {
        $this->directives = CompilerDirectives::createWithDefaults();
        $this->evaluator = $evaluator ?? new Evaluator();
        $this->scope = new Scope(
            evaluator: $this->evaluator,
        );
    }

    abstract protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface;

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
     * @throws CompilerException
     */
    protected function popScope(
        bool $merge = false,
    ): void {
        $scope = \array_pop($this->scopeStack);

        if ($scope === null) {
            throw CompilerException::fromCannotPopOptimizerScope();
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
                Type::BOOL => $node->value->operand === 'true',
                Type::NULL => null,
            },
        );

        return [
            $node,
        ];
    }

    /**
     * @throws CompilerException
     */
    protected function optimizeBlockBody(
        BlockNode $node,
    ): BlockNode {
        $this->pushScope();

        $nodes = $this->optimizeNodes($node->body);

        $this->popScope();

        return new BlockNode(
            name: $node->name,
            body: $nodes,
        );
    }

    /**
     * @throws CompilerException
     */
    protected function optimizeForBody(
        ForNode $node,
    ): ForNode {
        $this->pushScope(inherit: true);

        $nodes = $this->optimizeNodes($node->body);

        $this->popScope(merge: true);

        return new ForNode(
            value: $node->value,
            iterator: $node->iterator,
            body: $nodes,
            key: $node->key,
        );
    }

    /**
     * @throws CompilerException
     */
    protected function optimizeDoWhileBody(
        DoWhileNode $node,
    ): DoWhileNode {
        $this->pushScope(inherit: true);

        $nodes = $this->optimizeNodes($node->body);

        $this->popScope(merge: true);

        return new DoWhileNode(
            operand: $node->operand,
            body: $nodes,
        );
    }

    /**
     * @throws CompilerException
     */
    protected function optimizeWhileBody(
        WhileNode $node,
    ): WhileNode {
        $this->pushScope(inherit: true);

        $nodes = $this->optimizeNodes($node->body);

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
    ): OptimizerResultInterface {
        $oldStream = clone $stream;
        $stream = static::optimizer($stream);

        $this->directives = CompilerDirectives::createWithDefaults();

        return OptimizerResult::create(
            oldStream: $oldStream,
            newStream: $stream,
        );
    }
}
