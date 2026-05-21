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

namespace Unit\View\Lumi\Optimizer\Sccp;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\OptimizerResultInterface;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;

class SccpOptimizerTest extends TestCase
{
    private SccpOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new SccpOptimizer();
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
        $node = new CommentNode(
            text: 'kept',
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

    public function testDirectiveNodeIsKeptInStream(): void
    {
        $declare = new DeclareNode(
            directive: LiteralNode::createString('lumi.autoescape'),
            value: LiteralNode::createBool(false),
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

    public function testAssignmentWithoutComputedValueIsKeptAsIs(): void
    {
        $node = new AssignmentNode(
            name: new IdentifierNode(
                name: 'x',
            ),
            value: new IdentifierNode(
                name: 'y',
            ),
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

    public function testAssignmentWithComputedLiteralValueIsRewrittenToLiteral(): void
    {
        $result = $this->optimize(
            nodes: [
                new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'x',
                    ),
                    value: LiteralNode::createInt(1),
                    operator: AssignmentSymbol::ASSIGN,
                ),
                new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'x',
                    ),
                    value: LiteralNode::createInt(2),
                    operator: AssignmentSymbol::ADD,
                ),
            ],
        );

        self::assertCount(2, $result->stream->nodes);
        self::assertInstanceOf(AssignmentNode::class, $result->stream->nodes[1]);
        self::assertSame(AssignmentSymbol::ASSIGN, $result->stream->nodes[1]->operator);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[1]->value);
        self::assertSame('3', $result->stream->nodes[1]->value->operand);
        self::assertTrue($result->changed);
    }

    public function testEchoOfEmptyStringLiteralIsRemoved(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: LiteralNode::createString(''),
                ),
            ],
        );

        self::assertSame([], $result->stream->nodes);
        self::assertTrue($result->changed);
    }

    public function testEchoOfStringLiteralIsKeptWhenAutoescapeIsEnabled(): void
    {
        $echo = new EchoNode(
            operand: LiteralNode::createString('hello'),
        );

        $result = $this->optimize(
            nodes: [
                $echo,
            ],
        );

        self::assertSame(
            [
                $echo,
            ],
            $result->stream->nodes,
        );
    }

    public function testEchoOfStringLiteralIsConvertedToTextWhenAutoescapeIsDisabled(): void
    {
        $result = $this->optimize(
            nodes: [
                new DeclareNode(
                    directive: LiteralNode::createString('lumi.autoescape'),
                    value: LiteralNode::createBool(false),
                ),
                new EchoNode(
                    operand: LiteralNode::createString('hello'),
                ),
            ],
        );

        self::assertCount(2, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[1]);
        self::assertSame('hello', $result->stream->nodes[1]->text);
        self::assertTrue($result->changed);
    }

    public function testEchoOfIntLiteralIsConvertedToText(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: LiteralNode::createInt(42),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('42', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testEchoOfBoolLiteralIsConvertedToText(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: LiteralNode::createBool(true),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('1', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testEchoOfNullLiteralIsRemoved(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: LiteralNode::createNull(),
                ),
            ],
        );

        self::assertSame([], $result->stream->nodes);
        self::assertTrue($result->changed);
    }

    public function testEchoOfFoldedBinaryOpBecomesText(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: new BinaryOpNode(
                        left: LiteralNode::createInt(1),
                        right: LiteralNode::createInt(2),
                        operator: BinarySymbol::ADD,
                    ),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('3', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testEchoOfNonFoldableExpressionIsKeptAsIs(): void
    {
        $echo = new EchoNode(
            operand: new IdentifierNode(
                name: 'name',
            ),
        );

        $result = $this->optimize(
            nodes: [
                $echo,
            ],
        );

        self::assertSame(
            [
                $echo,
            ],
            $result->stream->nodes,
        );

        self::assertFalse($result->changed);
    }

    public function testGroupOfBinaryOpIsFolded(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: new GroupNode(
                        operand: new BinaryOpNode(
                            left: LiteralNode::createInt(2),
                            right: LiteralNode::createInt(3),
                            operator: BinarySymbol::MULTIPLY,
                        ),
                    ),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('6', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testGroupOfUnaryOpIsFolded(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: new GroupNode(
                        operand: new UnaryOpNode(
                            operand: LiteralNode::createInt(5),
                            operator: UnarySymbol::NEGATE,
                        ),
                    ),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('-5', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testGroupOfLiteralIsUnwrappedToLiteral(): void
    {
        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: new GroupNode(
                        operand: LiteralNode::createInt(7),
                    ),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('7', $result->stream->nodes[0]->text);
        self::assertTrue($result->changed);
    }

    public function testBinaryOpAtTopLevelIsFoldedToLiteral(): void
    {
        $result = $this->optimize(
            nodes: [
                new BinaryOpNode(
                    left: LiteralNode::createInt(10),
                    right: LiteralNode::createInt(4),
                    operator: BinarySymbol::SUBTRACT,
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]);
        self::assertSame('6', $result->stream->nodes[0]->operand);
        self::assertTrue($result->changed);
    }

    public function testNonFoldableBinaryOpFallsBackToOriginalNode(): void
    {
        $node = new BinaryOpNode(
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
            operator: BinarySymbol::ADD,
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

    public function testConcatOfTwoLiteralsIsFoldedToString(): void
    {
        $result = $this->optimize(
            nodes: [
                new BinaryOpNode(
                    left: LiteralNode::createString('foo'),
                    right: LiteralNode::createString('bar'),
                    operator: BinarySymbol::CONCAT,
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]);
        self::assertSame('foobar', $result->stream->nodes[0]->operand);
        self::assertTrue($result->changed);
    }

    public function testNestedConcatOperandsAreFlattenedAndFolded(): void
    {
        $result = $this->optimize(
            nodes: [
                new BinaryOpNode(
                    left: new BinaryOpNode(
                        left: LiteralNode::createString('a'),
                        right: LiteralNode::createString('b'),
                        operator: BinarySymbol::CONCAT,
                    ),
                    right: LiteralNode::createString('c'),
                    operator: BinarySymbol::CONCAT,
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]);
        self::assertSame('abc', $result->stream->nodes[0]->operand);
        self::assertTrue($result->changed);
    }

    public function testConcatWithNonLiteralOperandIsKeptAsConcatNode(): void
    {
        $result = $this->optimize(
            nodes: [
                new BinaryOpNode(
                    left: LiteralNode::createString('foo'),
                    right: new IdentifierNode(
                        name: 'name',
                    ),
                    operator: BinarySymbol::CONCAT,
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConcatNode::class, $result->stream->nodes[0]);
        self::assertCount(2, $result->stream->nodes[0]->operands);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]->operands[0]);
        self::assertSame('foo', $result->stream->nodes[0]->operands[0]->operand);
        self::assertInstanceOf(IdentifierNode::class, $result->stream->nodes[0]->operands[1]);
        self::assertSame('name', $result->stream->nodes[0]->operands[1]->name);
        self::assertTrue($result->changed);
    }

    public function testUnaryOpIsFoldedToLiteral(): void
    {
        $result = $this->optimize(
            nodes: [
                new UnaryOpNode(
                    operand: LiteralNode::createBool(true),
                    operator: UnarySymbol::NOT,
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]);
        self::assertSame('false', $result->stream->nodes[0]->operand);
        self::assertTrue($result->changed);
    }

    public function testNonFoldableUnaryOpFallsBackToOriginalNode(): void
    {
        $node = new UnaryOpNode(
            operand: new IdentifierNode(
                name: 'a',
            ),
            operator: UnarySymbol::NOT,
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

    public function testBlockBodyIsOptimizedRecursively(): void
    {
        $result = $this->optimize(
            nodes: [
                new BlockNode(
                    name: 'main',
                    body: [
                        new BinaryOpNode(
                            left: LiteralNode::createInt(2),
                            right: LiteralNode::createInt(3),
                            operator: BinarySymbol::ADD,
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(BlockNode::class, $result->stream->nodes[0]);
        self::assertSame('main', $result->stream->nodes[0]->name);
        self::assertCount(1, $result->stream->nodes[0]->body);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]->body[0]);
        self::assertSame('5', $result->stream->nodes[0]->body[0]->operand);
        self::assertTrue($result->changed);
    }

    public function testDoWhileBodyIsOptimizedRecursively(): void
    {
        $result = $this->optimize(
            nodes: [
                new DoWhileNode(
                    operand: new IdentifierNode(
                        name: 'cond',
                    ),
                    body: [
                        new BinaryOpNode(
                            left: LiteralNode::createInt(1),
                            right: LiteralNode::createInt(1),
                            operator: BinarySymbol::ADD,
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(DoWhileNode::class, $result->stream->nodes[0]);
        self::assertCount(1, $result->stream->nodes[0]->body);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]->body[0]);
        self::assertSame('2', $result->stream->nodes[0]->body[0]->operand);
        self::assertTrue($result->changed);
    }

    public function testTextNodesAreMergedAcrossStream(): void
    {
        $result = $this->optimize(
            nodes: [
                new TextNode(
                    text: 'foo',
                ),
                new TextNode(
                    text: 'bar',
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]);
        self::assertSame('foobar', $result->stream->nodes[0]->text);
        self::assertSame(TextContext::NONE, $result->stream->nodes[0]->context);
        self::assertTrue($result->changed);
    }

    public function testConditionalWithFoldableOperandRewritesCondition(): void
    {
        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
                    operand: new BinaryOpNode(
                        left: LiteralNode::createInt(1),
                        right: LiteralNode::createInt(1),
                        operator: BinarySymbol::ADD,
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
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]->operand);
        self::assertSame('2', $result->stream->nodes[0]->operand->operand);
        self::assertCount(1, $result->stream->nodes[0]->body);
        self::assertInstanceOf(TextNode::class, $result->stream->nodes[0]->body[0]);
        self::assertSame('body', $result->stream->nodes[0]->body[0]->text);
        self::assertTrue($result->changed);
    }

    public function testConditionalWithUnfoldableOperandIsKept(): void
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

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertInstanceOf(IdentifierNode::class, $result->stream->nodes[0]->operand);
        self::assertSame('cond', $result->stream->nodes[0]->operand->name);
        self::assertFalse($result->changed);
    }

    public function testConditionalBranchOperandIsFolded(): void
    {
        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
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
                            operand: new BinaryOpNode(
                                left: LiteralNode::createInt(3),
                                right: LiteralNode::createInt(4),
                                operator: BinarySymbol::ADD,
                            ),
                            body: [
                                new TextNode(
                                    text: 'branch',
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

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertCount(1, $result->stream->nodes[0]->branches);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]->branches[0]->operand);
        self::assertSame('7', $result->stream->nodes[0]->branches[0]->operand->operand);
        self::assertTrue($result->changed);
    }

    public function testConditionalBranchWithUnfoldableOperandIsKept(): void
    {
        $branchOperand = new IdentifierNode(
            name: 'other',
        );

        $result = $this->optimize(
            nodes: [
                new ConditionalNode(
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
                            operand: $branchOperand,
                            body: [
                                new TextNode(
                                    text: 'branch',
                                ),
                            ],
                        ),
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(ConditionalNode::class, $result->stream->nodes[0]);
        self::assertCount(1, $result->stream->nodes[0]->branches);
        self::assertSame($branchOperand, $result->stream->nodes[0]->branches[0]->operand);
        self::assertFalse($result->changed);
    }

    public function testAssignmentInsideDoWhileBodyIsNotFoldedAcrossIterations(): void
    {
        $compoundAssignment = new AssignmentNode(
            name: new IdentifierNode(
                name: 'x',
            ),
            value: LiteralNode::createInt(2),
            operator: AssignmentSymbol::ADD,
        );

        $result = $this->optimize(
            nodes: [
                new DoWhileNode(
                    operand: new IdentifierNode(
                        name: 'cond',
                    ),
                    body: [
                        new AssignmentNode(
                            name: new IdentifierNode(
                                name: 'x',
                            ),
                            value: LiteralNode::createInt(1),
                            operator: AssignmentSymbol::ASSIGN,
                        ),
                        $compoundAssignment,
                    ],
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(DoWhileNode::class, $result->stream->nodes[0]);
        self::assertCount(2, $result->stream->nodes[0]->body);
        self::assertSame($compoundAssignment, $result->stream->nodes[0]->body[1]);
    }

    public function testEchoOfReducibleExpressionIsRewrappedWhenResultIsNotLiteral(): void
    {
        $name = new IdentifierNode(
            name: 'name',
        );

        $result = $this->optimize(
            nodes: [
                new EchoNode(
                    operand: new BinaryOpNode(
                        left: LiteralNode::createString('hello-'),
                        right: $name,
                        operator: BinarySymbol::CONCAT,
                    ),
                ),
            ],
        );

        self::assertCount(1, $result->stream->nodes);
        self::assertInstanceOf(EchoNode::class, $result->stream->nodes[0]);
        self::assertInstanceOf(ConcatNode::class, $result->stream->nodes[0]->operand);
        self::assertCount(2, $result->stream->nodes[0]->operand->operands);
        self::assertInstanceOf(LiteralNode::class, $result->stream->nodes[0]->operand->operands[0]);
        self::assertSame('hello-', $result->stream->nodes[0]->operand->operands[0]->operand);
        self::assertSame($name, $result->stream->nodes[0]->operand->operands[1]);
        self::assertTrue($result->changed);
    }
}
