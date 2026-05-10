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

namespace Unit\View\Lumi\Optimizer\Dce;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\OptimizerResultInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;

class DceOptimizerTest extends TestCase
{
    private DceOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new DceOptimizer();
    }

    /**
     * @param NodeInterface[] $nodes
     */
    private function optimize(
        array $nodes,
    ): OptimizerResultInterface {
        return $this->optimizer->optimize(
            stream: new NodeStream(
                nodes: $nodes,
            ),
        );
    }

    public function testOptimizeReturnsOptimizerResult(): void
    {
        $result = $this->optimize(
            nodes: [],
        );

        self::assertInstanceOf(OptimizerResultInterface::class, $result);
    }

    public function testOptimizeWithEmptyStreamYieldsEmptyStream(): void
    {
        $result = $this->optimize(
            nodes: [],
        );

        self::assertSame([], $result->stream->nodes);
        self::assertFalse($result->changed);
    }

    public function testUnknownNodeIsKeptUnchanged(): void
    {
        $node = new IdentifierNode(
            name: 'foo',
        );

        $result = $this->optimize(
            nodes: [
                $node,
            ],
        );

        self::assertSame(
            [
                $node,
            ],
            $result->stream->nodes,
        );
        self::assertFalse($result->changed);
    }

    public function testCommentNodeIsStrippedByDefault(): void
    {
        $result = $this->optimize(
            nodes: [
                new CommentNode(
                    text: 'gone',
                ),
            ],
        );

        self::assertSame([], $result->stream->nodes);
        self::assertTrue($result->changed);
    }

    public function testCommentNodeIsKeptWhenStripCommentsDirectiveIsFalse(): void
    {
        $declare = new DeclareNode(
            directive: LiteralNode::createString('lumi.strip_comments'),
            value: LiteralNode::createBool(false),
        );

        $comment = new CommentNode(
            text: 'kept',
        );

        $result = $this->optimize(
            nodes: [
                $declare,
                $comment,
            ],
        );

        self::assertCount(2, $result->stream->nodes);
        self::assertSame($declare, $result->stream->nodes[0]);
        self::assertSame($comment, $result->stream->nodes[1]);
    }

    public function testAdjacentTextNodesWithNoneContextAreMerged(): void
    {
        $result = $this->optimize(
            nodes: [
                new TextNode(
                    text: 'foo',
                ),
                new TextNode(
                    text: 'bar',
                ),
                new TextNode(
                    text: 'baz',
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('foobarbaz', $result->stream->nodes[0]->text);
        self::assertSame(TextContext::NONE, $result->stream->nodes[0]->context);
        self::assertTrue($result->changed);
    }

    public function testRawTextNodeStopsTextMerging(): void
    {
        $raw = new TextNode(
            text: 'raw',
            context: TextContext::RAW,
        );

        $result = $this->optimize(
            nodes: [
                new TextNode(
                    text: 'foo',
                ),
                $raw,
                new TextNode(
                    text: 'bar',
                ),
            ],
        );

        self::assertCount(3, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('foo', $result->stream->nodes[0]->text);
        self::assertSame($raw, $result->stream->nodes[1]);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[2]);
        self::assertSame('bar', $result->stream->nodes[2]->text);
    }

    public function testSingleTextNodeIsNotReplaced(): void
    {
        $node = new TextNode(
            text: 'untouched',
        );

        $result = $this->optimize(
            nodes: [
                $node,
            ],
        );

        self::assertSame(
            [
                $node,
            ],
            $result->stream->nodes,
        );
        self::assertFalse($result->changed);
    }

    public function testConditionalWithLiteralTrueOperandInlinesBody(): void
    {
        $body = new TextNode(
            text: 'inlined',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(true),
                    body: [
                        $body,
                    ],
                    else: [
                        new TextNode(
                            text: 'else',
                        ),
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                $body,
            ],
            $result->stream->nodes,
        );
        self::assertTrue($result->changed);
    }

    public function testConditionalWithLiteralFalseOperandInlinesElse(): void
    {
        $else = new TextNode(
            text: 'fallback',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'unreachable',
                        ),
                    ],
                    else: [
                        $else,
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                $else,
            ],
            $result->stream->nodes,
        );
        self::assertTrue($result->changed);
    }

    public function testConditionalWithLiteralFalseOperandAndNoElseRemovesEntireBlock(): void
    {
        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'unreachable',
                        ),
                    ],
                ),
            ],
        );

        self::assertSame([], $result->stream->nodes);
        self::assertTrue($result->changed);
    }

    public function testConditionalWithUnknownOperandIsKept(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'cond',
            ),
            body: [
                new TextNode(
                    text: 'body',
                ),
            ],
        );

        $result = $this->optimize(
            nodes: [
                $node,
            ],
        );

        self::assertSame(
            [
                $node,
            ],
            $result->stream->nodes,
        );
        self::assertFalse($result->changed);
    }

    public function testConditionalWithBranchesAndLiteralTrueOperandInlinesIfBody(): void
    {
        $body = new TextNode(
            text: 'main',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(true),
                    body: [
                        $body,
                    ],
                    branches: [
                        new ConditionalBranchNode(
                            operand: new IdentifierNode(
                                name: 'other',
                            ),
                            body: [
                                new TextNode(
                                    text: 'other-body',
                                ),
                            ],
                        ),
                    ],
                    else: [
                        new TextNode(
                            text: 'fallback',
                        ),
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                $body,
            ],
            $result->stream->nodes,
        );
        self::assertTrue($result->changed);
    }

    public function testConditionalWithBranchesAndUnknownOperandIsKept(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'cond',
            ),
            body: [
                new TextNode(
                    text: 'main',
                ),
            ],
            branches: [
                new ConditionalBranchNode(
                    operand: new IdentifierNode(
                        name: 'other',
                    ),
                    body: [
                        new TextNode(
                            text: 'other-body',
                        ),
                    ],
                ),
            ],
        );

        $result = $this->optimize(
            nodes: [
                $node,
            ],
        );

        self::assertSame(
            [
                $node,
            ],
            $result->stream->nodes,
        );
        self::assertFalse($result->changed);
    }

    public function testConditionalWithFalseOperandAndOnlyUnknownBranchesReturnsElse(): void
    {
        $else = new TextNode(
            text: 'fallback',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'main',
                        ),
                    ],
                    branches: [
                        new ConditionalBranchNode(
                            operand: new IdentifierNode(
                                name: 'unknown',
                            ),
                            body: [
                                new TextNode(
                                    text: 'branch-body',
                                ),
                            ],
                        ),
                    ],
                    else: [
                        $else,
                    ],
                ),
            ],
        );

        self::assertSame(
            [
                $else,
            ],
            $result->stream->nodes,
        );
        self::assertTrue($result->changed);
    }

    public function testConditionalWithFalseOperandAndFalseBranchReconstructsFromBranch(): void
    {
        $branchBody = new TextNode(
            text: 'branch',
        );

        $else = new TextNode(
            text: 'fallback',
        );

        $falseBranchOperand = LiteralNode::createBool(false);

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'main',
                        ),
                    ],
                    branches: [
                        new ConditionalBranchNode(
                            operand: $falseBranchOperand,
                            body: [
                                $branchBody,
                            ],
                        ),
                    ],
                    else: [
                        $else,
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertSame($falseBranchOperand, $result->stream->nodes[0]->operand);

        self::assertSame(
            [
                $branchBody,
            ],
            $result->stream->nodes[0]->body,
        );

        self::assertSame([], $result->stream->nodes[0]->branches);

        self::assertSame(
            [
                $else,
            ],
            $result->stream->nodes[0]->else,
        );

        self::assertTrue($result->changed);
    }

    public function testConditionalWithFalseOperandAndTrueBranchPromotesItAsNewElse(): void
    {
        $falseBranchOperand = LiteralNode::createBool(false);

        $falseBranchBody = new TextNode(
            text: 'false-branch',
        );

        $trueBranchBody = new TextNode(
            text: 'true-branch',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'main',
                        ),
                    ],
                    branches: [
                        new ConditionalBranchNode(
                            operand: $falseBranchOperand,
                            body: [
                                $falseBranchBody,
                            ],
                        ),
                        new ConditionalBranchNode(
                            operand: LiteralNode::createBool(true),
                            body: [
                                $trueBranchBody,
                            ],
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertSame($falseBranchOperand, $result->stream->nodes[0]->operand);

        self::assertSame(
            [
                $falseBranchBody,
            ],
            $result->stream->nodes[0]->body,
        );

        self::assertSame([], $result->stream->nodes[0]->branches);

        self::assertSame(
            [
                $trueBranchBody,
            ],
            $result->stream->nodes[0]->else,
        );

        self::assertTrue($result->changed);
    }

    public function testConditionalWithFalseOperandPreservesUnknownBranchesBeforeFirstFalseBranch(): void
    {
        $unknownBranchOperand = new IdentifierNode(
            name: 'unknown',
        );

        $unknownBranchBody = new TextNode(
            text: 'unknown-branch',
        );

        $falseBranchOperand = LiteralNode::createBool(false);

        $falseBranchBody = new TextNode(
            text: 'false-branch',
        );

        $else = new TextNode(
            text: 'fallback',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'main',
                        ),
                    ],
                    branches: [
                        new ConditionalBranchNode(
                            operand: $unknownBranchOperand,
                            body: [
                                $unknownBranchBody,
                            ],
                        ),
                        new ConditionalBranchNode(
                            operand: $falseBranchOperand,
                            body: [
                                $falseBranchBody,
                            ],
                        ),
                    ],
                    else: [
                        $else,
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertSame($falseBranchOperand, $result->stream->nodes[0]->operand);

        self::assertSame(
            [
                $falseBranchBody,
            ],
            $result->stream->nodes[0]->body,
        );

        self::assertCount(1, $result->stream->nodes[0]->branches);
        self::assertInstanceOf(ConditionalBranchNode::class, $result->stream->nodes[0]->branches[0]);
        self::assertSame($unknownBranchOperand, $result->stream->nodes[0]->branches[0]->operand);

        self::assertSame(
            [
                $else,
            ],
            $result->stream->nodes[0]->else,
        );

        self::assertTrue($result->changed);
    }

    public function testWhileWithLiteralFalseOperandIsRemoved(): void
    {
        $result = $this->optimize(
            nodes: [
                new WhileNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        new TextNode(
                            text: 'unreachable',
                        ),
                    ],
                ),
            ],
        );

        self::assertSame([], $result->stream->nodes);
        self::assertTrue($result->changed);
    }

    public function testWhileWithLiteralTrueOperandIsKept(): void
    {
        $result = $this->optimize(
            nodes: [
                new WhileNode(
                    operand: LiteralNode::createBool(true),
                    body: [
                        new TextNode(
                            text: 'body',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(WhileNode::class, $result->stream->nodes[0]);
    }

    public function testWhileWithUnknownOperandIsKept(): void
    {
        $result = $this->optimize(
            nodes: [
                new WhileNode(
                    operand: new IdentifierNode(
                        name: 'cond',
                    ),
                    body: [
                        new TextNode(
                            text: 'body',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(WhileNode::class, $result->stream->nodes[0]);
    }

    public function testDoWhileWithLiteralFalseOperandReducesToBody(): void
    {
        $body = new TextNode(
            text: 'runs-once',
        );

        $result = $this->optimize(
            nodes: [
                new DoWhileNode(
                    operand: LiteralNode::createBool(false),
                    body: [
                        $body,
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('runs-once', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testDoWhileWithUnknownOperandIsKept(): void
    {
        $result = $this->optimize(
            nodes: [
                new DoWhileNode(
                    operand: new IdentifierNode(
                        name: 'cond',
                    ),
                    body: [
                        new TextNode(
                            text: 'body',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(DoWhileNode::class, $result->stream->nodes[0]);
    }

    public function testDoWhileWithLiteralTrueOperandIsKept(): void
    {
        $result = $this->optimize(
            nodes: [
                new DoWhileNode(
                    operand: LiteralNode::createBool(true),
                    body: [
                        new TextNode(
                            text: 'body',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(DoWhileNode::class, $result->stream->nodes[0]);
    }

    public function testBreakDrainsSubsequentStatements(): void
    {
        $kept = new TextNode(
            text: 'kept',
        );

        $result = $this->optimize(
            nodes: [
                $kept,
                new BreakNode(),
                new TextNode(
                    text: 'dropped-1',
                ),
                new TextNode(
                    text: 'dropped-2',
                ),
            ],
        );

        self::assertSame(
            [
                $kept,
            ],
            $result->stream->nodes,
        );
        self::assertTrue($result->changed);
    }

    public function testContinueDrainsSubsequentStatements(): void
    {
        $kept = new TextNode(
            text: 'kept',
        );

        $result = $this->optimize(
            nodes: [
                $kept,
                new ContinueNode(),
                new TextNode(
                    text: 'dropped',
                ),
            ],
        );

        self::assertSame(
            [
                $kept,
            ],
            $result->stream->nodes,
        );
        self::assertTrue($result->changed);
    }

    public function testAssignmentNodeIsPreserved(): void
    {
        $node = new AssignmentNode(
            name: new IdentifierNode(
                name: 'x',
            ),
            value: LiteralNode::createInt(1),
            operator: AssignmentSymbol::ASSIGN,
        );

        $result = $this->optimize(
            nodes: [
                $node,
            ],
        );

        self::assertSame(
            [
                $node,
            ],
            $result->stream->nodes,
        );
        self::assertFalse($result->changed);
    }

    public function testAssignedLiteralFlowsIntoLaterConditional(): void
    {
        $body = new TextNode(
            text: 'inlined',
        );

        $result = $this->optimize(
            nodes: [
                new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'flag',
                    ),
                    value: LiteralNode::createBool(true),
                    operator: AssignmentSymbol::ASSIGN,
                ),
                new ConditionalNode(
                    operand: new IdentifierNode(
                        name: 'flag',
                    ),
                    body: [
                        $body,
                    ],
                ),
            ],
        );

        self::assertCount(2, $result->stream->nodes);
        self::assertInstanceOf(AssignmentNode::class, $result->stream->nodes[0]);
        self::assertSame($body, $result->stream->nodes[1]);
        self::assertTrue($result->changed);
    }

    public function testBlockBodyIsOptimizedRecursively(): void
    {
        $result = $this->optimize(
            nodes: [
                new BlockNode(
                    name: 'main',
                    body: [
                        new CommentNode(
                            text: 'gone',
                        ),
                        new TextNode(
                            text: 'kept',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(BlockNode::class, $result->stream->nodes[0]);
        self::assertSame('main', $result->stream->nodes[0]->name);
        self::assertCount(1, $result->stream->nodes[0]->body);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]->body[0]);
        self::assertSame('kept', $result->stream->nodes[0]->body[0]->text);
        self::assertTrue($result->changed);
    }

    public function testForBodyIsOptimizedRecursively(): void
    {
        $result = $this->optimize(
            nodes: [
                new ForNode(
                    value: new IdentifierNode(
                        name: 'item',
                    ),
                    iterator: new IdentifierNode(
                        name: 'items',
                    ),
                    body: [
                        new CommentNode(
                            text: 'gone',
                        ),
                        new TextNode(
                            text: 'kept',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ForNode::class, $result->stream->nodes[0]);
        self::assertCount(1, $result->stream->nodes[0]->body);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]->body[0]);
        self::assertSame('kept', $result->stream->nodes[0]->body[0]->text);
        self::assertTrue($result->changed);
    }

    public function testWhileBodyIsOptimizedRecursivelyWhenOperandIsTruthy(): void
    {
        $result = $this->optimize(
            nodes: [
                new WhileNode(
                    operand: LiteralNode::createBool(true),
                    body: [
                        new CommentNode(
                            text: 'gone',
                        ),
                        new TextNode(
                            text: 'kept',
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(WhileNode::class, $result->stream->nodes[0]);
        self::assertCount(1, $result->stream->nodes[0]->body);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]->body[0]);
        self::assertSame('kept', $result->stream->nodes[0]->body[0]->text);
    }

    public function testDirectiveNodeIsKeptInStream(): void
    {
        $declare = new DeclareNode(
            directive: LiteralNode::createString('lumi.strip_comments'),
            value: LiteralNode::createBool(true),
        );

        $result = $this->optimize(
            nodes: [
                $declare,
            ],
        );

        self::assertSame(
            [
                $declare,
            ],
            $result->stream->nodes,
        );
    }
}
