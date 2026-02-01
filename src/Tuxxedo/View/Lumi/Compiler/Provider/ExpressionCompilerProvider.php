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

namespace Tuxxedo\View\Lumi\Compiler\Provider;

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\CompilerStateFlag;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionCompilerProvider implements CompilerProviderInterface
{
    private function compileIdentifier(
        IdentifierNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        return \sprintf(
            '$__lumiVariables[\'%s\']',
            $compiler->escaper->js($node->name),
        );
    }

    private function compileLiteral(
        LiteralNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        return match ($node->type) {
            Type::STRING => '\'' . \str_replace('\'', '\\\'', $node->operand) . '\'',
            default => $node->operand,
        };
    }

    /**
     * @throws CompilerException
     */
    private function compileFunctionCall(
        FunctionCallNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if (!$node->name instanceof IdentifierNode) {
            throw CompilerException::fromFunctionCallNotIdentifier();
        }

        $arguments = [];

        if (\sizeof($node->arguments) > 0) {
            foreach ($node->arguments as $argument) {
                $arguments[] = $compiler->compileNode($argument, $stream);
            }
        }

        return \sprintf(
            '$this->functionCall(\'%s\', [%s])',
            $compiler->escaper->js(\mb_strtolower($node->name->name)),
            \join(', ', $arguments),
        );
    }

    /**
     * @throws CompilerException
     */
    private function compileMethodCall(
        MethodCallNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        $arguments = [];
        $caller = $compiler->compileNode($node->caller, $stream);

        if (\sizeof($node->arguments) > 0) {
            foreach ($node->arguments as $argument) {
                $arguments[] = $compiler->compileNode($argument, $stream);
            }
        }

        $nullSafe = $compiler->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS) || $node->nullSafe;

        return \sprintf(
            '$this->instanceCall(%s%s)%s->%s(%s)',
            $caller,
            $nullSafe
                ? ', true'
                : '',
            $nullSafe
                ? '?'
                : '',
            $node->name,
            \join(', ', $arguments),
        );
    }

    /**
     * @throws CompilerException
     */
    private function compileBinaryOp(
        BinaryOpNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        $nullSafe = $node->operator === BinarySymbol::NULL_SAFE_ACCESS ||
            $node->operator === BinarySymbol::NULL_COALESCE;

        if ($nullSafe) {
            $compiler->state->flag(CompilerStateFlag::NULL_SAFE_ACCESS);
        }

        $output = \sprintf(
            '%s %s %s',
            $compiler->compileNode($node->left, $stream),
            $node->operator->transform(),
            $compiler->compileNode($node->right, $stream),
        );

        if ($nullSafe) {
            $compiler->state->removeFlag(CompilerStateFlag::NULL_SAFE_ACCESS);
        }

        return $output;
    }

    private function compileAssignment(
        AssignmentNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($node->name instanceof PropertyAccessNode) {
            $oldState = $compiler->state->swap(NodeScope::EXPRESSION_ASSIGN);
            $accessor = $compiler->compileExpression($node->name->accessor);

            $compiler->state->swap($oldState);

            return \sprintf(
                '<?php %s->%s %s %s; ?>',
                $accessor,
                $node->name->property,
                $node->operator->transform(),
                $compiler->compileExpression($node->value),
            );
        } elseif ($node->name instanceof ArrayAccessNode) {
            return \sprintf(
                '<?php %s[%s] %s %s; ?>',
                $compiler->compileExpression($node->name->array),
                $node->name->key !== null
                    ? $compiler->compileExpression($node->name->key)
                    : '',
                $node->operator->transform(),
                $compiler->compileExpression($node->value),
            );
        }

        return \sprintf(
            '<?php $__lumiVariables[\'%s\'] %s %s; ?>',
            $compiler->escaper->js($node->name->name),
            $node->operator->transform(),
            $compiler->compileExpression($node->value),
        );
    }

    private function compileGroup(
        GroupNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        return \sprintf(
            '(%s)',
            $compiler->compileExpression($node->operand),
        );
    }

    private function compileConcat(
        ConcatNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        return \join(
            ' . ',
            \array_map(
                $compiler->compileExpression(...),
                $node->operands,
            ),
        );
    }

    private function compileArray(
        ArrayNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        return \sprintf(
            '[%s]',
            \join(
                ', ',
                \array_map(
                    static fn (ArrayItemNode $node): string => $compiler->compileNode($node, $stream),
                    $node->items,
                ),
            ),
        );
    }

    private function compileArrayAccess(
        ArrayAccessNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($node->key === null) {
            throw CompilerException::fromArrayAccessWithoutKey();
        }

        return \sprintf(
            '%s[%s]',
            $compiler->compileExpression($node->array),
            $compiler->compileExpression($node->key),
        );
    }

    private function compileArrayItem(
        ArrayItemNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($node->key !== null) {
            return \sprintf(
                '%s => %s',
                $compiler->compileExpression($node->key),
                $compiler->compileExpression($node->value),
            );
        }

        return $compiler->compileExpression($node->value);
    }

    private function compilePropertyAccess(
        PropertyAccessNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($compiler->state->is(NodeScope::EXPRESSION_ASSIGN)) {
            if ($node->nullSafe) {
                throw CompilerException::fromNullPropertyAssignment();
            }

            return \sprintf(
                '%s->%s',
                $compiler->compileExpression($node->accessor),
                $node->property,
            );
        }

        $nullSafe = $compiler->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS) ||
            $node->nullSafe;

        return \sprintf(
            '$this->propertyAccess(%s%s)%s->%s',
            $compiler->compileExpression($node->accessor),
            $nullSafe
                ? ', true'
                : '',
            $nullSafe
                ? '?'
                : '',
            $node->property,
        );
    }

    private function compileUnaryOp(
        UnaryOpNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($node->operator->isPost()) {
            return \sprintf(
                '%s%s',
                $compiler->compileExpression($node->operand),
                $node->operator->symbol(),
            );
        }

        return \sprintf(
            '%s%s',
            $node->operator->symbol(),
            $compiler->compileExpression($node->operand),
        );
    }

    private function compileFilterOrBitwiseOr(
        FilterOrBitwiseOrNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($node->right instanceof IdentifierNode) {
            return \sprintf(
                '($this->hasFilter(\'%1$s\') ? $this->filter(%2$s, \'%1$s\') : ((%2$s) | (%3$s)))',
                $compiler->escaper->js(\mb_strtolower($node->right->name)),
                $compiler->compileExpression($node->left),
                $compiler->compileExpression($node->right),
            );
        }

        return \sprintf(
            '((%s) | (%s))',
            $compiler->compileExpression($node->left),
            $compiler->compileExpression($node->right),
        );
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: IdentifierNode::class,
            handler: $this->compileIdentifier(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: LiteralNode::class,
            handler: $this->compileLiteral(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: FunctionCallNode::class,
            handler: $this->compileFunctionCall(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: MethodCallNode::class,
            handler: $this->compileMethodCall(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: BinaryOpNode::class,
            handler: $this->compileBinaryOp(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: AssignmentNode::class,
            handler: $this->compileAssignment(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: GroupNode::class,
            handler: $this->compileGroup(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: ConcatNode::class,
            handler: $this->compileConcat(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: ArrayNode::class,
            handler: $this->compileArray(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: ArrayAccessNode::class,
            handler: $this->compileArrayAccess(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: ArrayItemNode::class,
            handler: $this->compileArrayItem(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: PropertyAccessNode::class,
            handler: $this->compilePropertyAccess(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: UnaryOpNode::class,
            handler: $this->compileUnaryOp(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: FilterOrBitwiseOrNode::class,
            handler: $this->compileFilterOrBitwiseOr(...),
        );
    }
}
