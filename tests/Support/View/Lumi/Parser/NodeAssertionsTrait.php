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

namespace Support\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\IncludeNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\LumiNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Type;

trait NodeAssertionsTrait
{
    private function assertIdentifierNode(
        NodeInterface $node,
        string $expectedName,
    ): void {
        self::assertInstanceOf(
            IdentifierNode::class,
            $node,
        );

        self::assertSame(
            $expectedName,
            $node->name,
        );
    }

    private function assertLiteralNode(
        NodeInterface $node,
        string $expectedOperand,
        Type $expectedType,
    ): void {
        self::assertInstanceOf(
            LiteralNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperand,
            $node->operand,
        );

        self::assertSame(
            $expectedType,
            $node->type,
        );
    }

    private function assertUnaryOpNode(
        NodeInterface $node,
        UnarySymbol $expectedOperator,
    ): void {
        self::assertInstanceOf(
            UnaryOpNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperator,
            $node->operator,
        );
    }

    private function assertBinaryOpNode(
        NodeInterface $node,
        BinarySymbol $expectedOperator,
    ): void {
        self::assertInstanceOf(
            BinaryOpNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperator,
            $node->operator,
        );
    }

    private function assertGroupNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            GroupNode::class,
            $node,
        );
    }

    private function assertFilterOrBitwiseOrNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            FilterOrBitwiseOrNode::class,
            $node,
        );
    }

    private function assertArrayAccessNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            ArrayAccessNode::class,
            $node,
        );
    }

    private function assertArrayNode(
        NodeInterface $node,
        int $expectedItemCount,
    ): void {
        self::assertInstanceOf(
            ArrayNode::class,
            $node,
        );

        self::assertCount(
            $expectedItemCount,
            $node->items,
        );
    }

    private function assertArrayItemNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            ArrayItemNode::class,
            $node,
        );
    }

    private function assertPropertyAccessNode(
        NodeInterface $node,
        string $expectedProperty,
        bool $expectedNullSafe = false,
    ): void {
        self::assertInstanceOf(
            PropertyAccessNode::class,
            $node,
        );

        self::assertSame(
            $expectedProperty,
            $node->property,
        );

        self::assertSame(
            $expectedNullSafe,
            $node->nullSafe,
        );
    }

    private function assertFunctionCallNode(
        NodeInterface $node,
        int $expectedArgumentCount,
    ): void {
        self::assertInstanceOf(
            FunctionCallNode::class,
            $node,
        );

        self::assertCount(
            $expectedArgumentCount,
            $node->arguments,
        );
    }

    private function assertMethodCallNode(
        NodeInterface $node,
        string $expectedName,
        int $expectedArgumentCount,
        bool $expectedNullSafe = false,
    ): void {
        self::assertInstanceOf(
            MethodCallNode::class,
            $node,
        );

        self::assertSame(
            $expectedName,
            $node->name,
        );

        self::assertCount(
            $expectedArgumentCount,
            $node->arguments,
        );

        self::assertSame(
            $expectedNullSafe,
            $node->nullSafe,
        );
    }

    private function assertCommentNode(
        NodeInterface $node,
        string $expectedText,
    ): void {
        self::assertInstanceOf(
            CommentNode::class,
            $node,
        );

        self::assertSame(
            $expectedText,
            $node->text,
        );
    }

    private function assertTextNode(
        NodeInterface $node,
        string $expectedText,
        TextContext $expectedContext = TextContext::NONE,
    ): void {
        self::assertInstanceOf(
            TextNode::class,
            $node,
        );

        self::assertSame(
            $expectedText,
            $node->text,
        );

        self::assertSame(
            $expectedContext,
            $node->context,
        );
    }

    private function assertEchoNode(
        NodeInterface $node,
        TextContext $expectedContext = TextContext::NONE,
    ): void {
        self::assertInstanceOf(
            EchoNode::class,
            $node,
        );

        self::assertSame(
            $expectedContext,
            $node->context,
        );
    }

    private function assertDeclareNode(
        NodeInterface $node,
        string $expectedDirective,
        string $expectedValue,
        Type $expectedValueType,
    ): void {
        self::assertInstanceOf(
            DeclareNode::class,
            $node,
        );

        self::assertSame(
            $expectedDirective,
            $node->directive->operand,
        );

        self::assertSame(
            Type::STRING,
            $node->directive->type,
        );

        self::assertSame(
            $expectedValue,
            $node->value->operand,
        );

        self::assertSame(
            $expectedValueType,
            $node->value->type,
        );
    }

    private function assertLumiNode(
        NodeInterface $node,
        string $expectedTheme,
        string $expectedSourceCode,
    ): void {
        self::assertInstanceOf(
            LumiNode::class,
            $node,
        );

        self::assertSame(
            $expectedTheme,
            $node->theme,
        );

        self::assertSame(
            $expectedSourceCode,
            $node->sourceCode,
        );
    }

    private function assertIncludeNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            IncludeNode::class,
            $node,
        );
    }

    private function assertBlockNode(
        NodeInterface $node,
        string $expectedName,
        int $expectedBodyCount,
    ): void {
        self::assertInstanceOf(
            BlockNode::class,
            $node,
        );

        self::assertSame(
            $expectedName,
            $node->name,
        );

        self::assertCount(
            $expectedBodyCount,
            $node->body,
        );
    }

    private function assertLayoutNode(
        NodeInterface $node,
        string $expectedFile,
    ): void {
        self::assertInstanceOf(
            LayoutNode::class,
            $node,
        );

        self::assertSame(
            $expectedFile,
            $node->file,
        );
    }

    private function assertConditionalNode(
        NodeInterface $node,
        int $expectedBodyCount,
        int $expectedBranchCount,
        int $expectedElseCount,
    ): void {
        self::assertInstanceOf(
            ConditionalNode::class,
            $node,
        );

        self::assertCount(
            $expectedBodyCount,
            $node->body,
        );

        self::assertCount(
            $expectedBranchCount,
            $node->branches,
        );

        self::assertCount(
            $expectedElseCount,
            $node->else,
        );
    }

    private function assertConditionalBranchNode(
        NodeInterface $node,
        int $expectedBodyCount,
    ): void {
        self::assertInstanceOf(
            ConditionalBranchNode::class,
            $node,
        );

        self::assertCount(
            $expectedBodyCount,
            $node->body,
        );
    }

    private function assertAssignmentNode(
        NodeInterface $node,
        AssignmentSymbol $expectedOperator,
    ): void {
        self::assertInstanceOf(
            AssignmentNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperator,
            $node->operator,
        );
    }

    private function assertBreakNode(
        NodeInterface $node,
        ?int $expectedCount = null,
    ): void {
        self::assertInstanceOf(
            BreakNode::class,
            $node,
        );

        self::assertSame(
            $expectedCount,
            $node->count,
        );
    }

    private function assertContinueNode(
        NodeInterface $node,
        ?int $expectedCount = null,
    ): void {
        self::assertInstanceOf(
            ContinueNode::class,
            $node,
        );

        self::assertSame(
            $expectedCount,
            $node->count,
        );
    }

    private function assertWhileNode(
        NodeInterface $node,
        int $expectedBodyCount,
    ): void {
        self::assertInstanceOf(
            WhileNode::class,
            $node,
        );

        self::assertCount(
            $expectedBodyCount,
            $node->body,
        );
    }

    private function assertDoWhileNode(
        NodeInterface $node,
        int $expectedBodyCount,
    ): void {
        self::assertInstanceOf(
            DoWhileNode::class,
            $node,
        );

        self::assertCount(
            $expectedBodyCount,
            $node->body,
        );
    }

    private function assertForNode(
        NodeInterface $node,
        string $expectedValueName,
        int $expectedBodyCount,
        ?string $expectedKeyName = null,
    ): void {
        self::assertInstanceOf(
            ForNode::class,
            $node,
        );

        self::assertSame(
            $expectedValueName,
            $node->value->name,
        );

        self::assertCount(
            $expectedBodyCount,
            $node->body,
        );

        if ($expectedKeyName === null) {
            self::assertNull($node->key);
        } else {
            self::assertNotNull($node->key);
            self::assertSame(
                $expectedKeyName,
                $node->key->name,
            );
        }
    }
}
