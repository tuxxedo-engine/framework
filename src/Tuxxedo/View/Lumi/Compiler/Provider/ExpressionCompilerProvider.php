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

namespace Tuxxedo\View\Lumi\Compiler\Provider;

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\CompilerStateFlag;
use Tuxxedo\View\Lumi\Library\Function\PhpFunctionInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadataInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableParameter;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableParameterInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\CallDispatch;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\FilterCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TernaryNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionCompilerProvider implements CompilerProviderInterface
{
    private function compileIdentifier(
        IdentifierNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '$__lumiVariables[\'%s\']',
            $compiler->escaper->js($node->name),
        );
    }

    private function compileLiteral(
        LiteralNode $node,
    ): string {
        return match ($node->type) {
            Type::STRING => '\'' . \str_replace(
                [
                    '\\',
                    '\'',
                ],
                [
                    '\\\\',
                    '\\\'',
                ],
                $node->operand,
            ) . '\'',
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

        [$positional, $named] = $this->splitArguments($node->arguments);

        $positional = \array_map(
            static fn (ExpressionNodeInterface $argument): string => $compiler->compileNode($argument, $stream),
            $positional,
        );

        $named = \array_map(
            static fn (ExpressionNodeInterface $argument): string => $compiler->compileNode($argument, $stream),
            $named,
        );

        if ($node->dispatch === CallDispatch::NATIVE) {
            return \sprintf(
                '\\%s(%s)',
                $node->name->name,
                $this->joinCallArguments($positional, $named),
            );
        }

        $name = \mb_strtolower($node->name->name);

        if (
            $named !== [] &&
            $compiler->introspector->hasFunction($name)
        ) {
            $metadata = $compiler->introspector->getFunction($name);

            $this->validateNamedArguments(
                kind: 'Function',
                callable: $metadata->name,
                parameters: $metadata->parameters,
                positionalCount: \sizeof($positional),
                names: \array_keys($named),
            );
        }

        return \sprintf(
            '$this->functionCall(\'%s\', [%s])',
            $compiler->escaper->js($name),
            $this->joinArrayArguments($compiler, $positional, $named),
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
        $caller = $compiler->compileNode($node->caller, $stream);

        [$positional, $named] = $this->splitArguments($node->arguments);

        $positional = \array_map(
            static fn (ExpressionNodeInterface $argument): string => $compiler->compileNode($argument, $stream),
            $positional,
        );

        $named = \array_map(
            static fn (ExpressionNodeInterface $argument): string => $compiler->compileNode($argument, $stream),
            $named,
        );

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
            $this->joinCallArguments($positional, $named),
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
        if ($node->operator === BinarySymbol::IN || $node->operator === BinarySymbol::NOT_IN) {
            return \sprintf(
                '%s$this->contains(%s, %s)',
                $node->operator === BinarySymbol::NOT_IN
                    ? '!'
                    : '',
                $compiler->compileNode($node->left, $stream),
                $compiler->compileNode($node->right, $stream),
            );
        }

        $ownsNullSafeFlag = !$compiler->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS) &&
            (
                $node->operator === BinarySymbol::NULL_SAFE_ACCESS ||
                $node->operator === BinarySymbol::NULL_COALESCE
            );

        if ($ownsNullSafeFlag) {
            $compiler->state->flag(CompilerStateFlag::NULL_SAFE_ACCESS);
        }

        $left = $compiler->compileNode($node->left, $stream);
        $right = $compiler->compileNode($node->right, $stream);

        if ($node->operator !== BinarySymbol::NULL_SAFE_ACCESS) {
            $left = self::parenthesizeOperand(
                operand: $node->left,
                compiled: $left,
            );

            $right = self::parenthesizeOperand(
                operand: $node->right,
                compiled: $right,
            );
        }

        $output = \sprintf(
            '%s %s %s',
            $left,
            $node->operator->compile(),
            $right,
        );

        if ($ownsNullSafeFlag) {
            $compiler->state->removeFlag(CompilerStateFlag::NULL_SAFE_ACCESS);
        }

        return $output;
    }

    private function compileAssignment(
        AssignmentNode $node,
        CompilerInterface $compiler,
    ): string {
        if ($node->name instanceof PropertyAccessNode) {
            $oldState = $compiler->state->swap(NodeScope::EXPRESSION_ASSIGN);
            $accessor = $compiler->compileExpression($node->name->accessor);

            $compiler->state->swap($oldState);

            return \sprintf(
                '<?php %s->%s %s %s; ?>',
                $accessor,
                $node->name->property,
                $node->operator->compile(),
                $compiler->compileExpression($node->value),
            );
        } elseif ($node->name instanceof ArrayAccessNode) {
            return \sprintf(
                '<?php %s[%s] %s %s; ?>',
                $compiler->compileExpression($node->name->array),
                $node->name->key !== null
                    ? $compiler->compileExpression($node->name->key)
                    : '',
                $node->operator->compile(),
                $compiler->compileExpression($node->value),
            );
        }

        return \sprintf(
            '<?php $__lumiVariables[\'%s\'] %s %s; ?>',
            $compiler->escaper->js($node->name->name),
            $node->operator->compile(),
            $compiler->compileExpression($node->value),
        );
    }

    private function compileTernary(
        TernaryNode $node,
        CompilerInterface $compiler,
    ): string {
        if ($node->then === null) {
            return \sprintf(
                '((%s) ?: (%s))',
                $compiler->compileExpression($node->condition),
                $compiler->compileExpression($node->else),
            );
        }

        return \sprintf(
            '((%s) ? (%s) : (%s))',
            $compiler->compileExpression($node->condition),
            $compiler->compileExpression($node->then),
            $compiler->compileExpression($node->else),
        );
    }

    private function compileGroup(
        GroupNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '(%s)',
            $compiler->compileExpression($node->operand),
        );
    }

    private function compileConcat(
        ConcatNode $node,
        CompilerInterface $compiler,
    ): string {
        return \join(
            ' . ',
            \array_map(
                static fn (ExpressionNodeInterface $operand): string => $operand instanceof BinaryOpNode
                    ? '(' . $compiler->compileExpression($operand) . ')'
                    : $compiler->compileExpression($operand),
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
            self::parenthesizeOperand(
                operand: $node->operand,
                compiled: $compiler->compileExpression($node->operand),
            ),
        );
    }

    private static function parenthesizeOperand(
        ExpressionNodeInterface $operand,
        string $compiled,
    ): string {
        if (
            $operand instanceof BinaryOpNode ||
            $operand instanceof UnaryOpNode ||
            $operand instanceof ConcatNode ||
            (
                $operand instanceof LiteralNode &&
                \str_starts_with($operand->operand, '-')
            )
        ) {
            return '(' . $compiled . ')';
        }

        return $compiled;
    }

    /**
     * @throws CompilerException
     */
    private function compileFilterOrBitwiseOr(
        FilterOrBitwiseOrNode $node,
        CompilerInterface $compiler,
    ): string {
        if ($node->right instanceof IdentifierNode) {
            $name = \mb_strtolower($node->right->name);

            if ($compiler->introspector->hasFilter($name)) {
                return $this->emitDirectFilterCall(
                    compiler: $compiler,
                    metadata: $compiler->introspector->getFilter($name),
                    valueNode: $node->left,
                );
            }

            if ($compiler->introspector->hasPhpFilter($name)) {
                return $this->emitPhpFilterCall(
                    compiler: $compiler,
                    handler: $compiler->introspector->getPhpFilter($name),
                    valueNode: $node->left,
                );
            }

            if (!$compiler->introspector->hasAnyFilters()) {
                return \sprintf(
                    '$this->filter(%s, \'%s\')',
                    $compiler->compileExpression($node->left),
                    $compiler->escaper->js($name),
                );
            }

            return \sprintf(
                '((%s) | (%s))',
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

    /**
     * @throws CompilerException
     */
    private function compileFilterCall(
        FilterCallNode $node,
        CompilerInterface $compiler,
    ): string {
        $name = \mb_strtolower($node->name);

        if ($compiler->introspector->hasFilter($name)) {
            return $this->emitDirectFilterCall(
                compiler: $compiler,
                metadata: $compiler->introspector->getFilter($name),
                valueNode: $node->left,
                argumentNodes: $node->arguments,
            );
        }

        if ($compiler->introspector->hasPhpFilter($name)) {
            return $this->emitPhpFilterCall(
                compiler: $compiler,
                handler: $compiler->introspector->getPhpFilter($name),
                valueNode: $node->left,
                argumentNodes: $node->arguments,
            );
        }

        if (!$compiler->introspector->hasAnyFilters()) {
            [$positional, $named] = $this->splitArguments($node->arguments);

            return \sprintf(
                '$this->filter(%s, \'%s\', [%s])',
                $compiler->compileExpression($node->left),
                $compiler->escaper->js($name),
                $this->joinArrayArguments(
                    $compiler,
                    \array_map(
                        $compiler->compileExpression(...),
                        $positional,
                    ),
                    \array_map(
                        $compiler->compileExpression(...),
                        $named,
                    ),
                ),
            );
        }

        throw CompilerException::fromUnknownFilterCall($node->name);
    }

    /**
     * @param array<int|string, ExpressionNodeInterface> $argumentNodes
     *
     * @throws CompilerException
     */
    private function emitDirectFilterCall(
        CompilerInterface $compiler,
        CallableMetadataInterface $metadata,
        ExpressionNodeInterface $valueNode,
        array $argumentNodes = [],
    ): string {
        [$positional, $named] = $this->splitArguments($argumentNodes);

        $given = 1 + \sizeof($positional);

        if ($named !== []) {
            $this->validateNamedArguments(
                kind: 'Filter',
                callable: $metadata->name,
                parameters: $metadata->parameters,
                positionalCount: $given,
                names: \array_keys($named),
            );
        } elseif (
            $given < $metadata->minimumArguments ||
            (
                $metadata->maximumArguments !== null &&
                $given > $metadata->maximumArguments
            )
        ) {
            throw CompilerException::fromFilterArgumentCount(
                filter: $metadata->name,
                given: $given,
                minimum: $metadata->minimumArguments,
                maximum: $metadata->maximumArguments,
            );
        }

        $positional = [
            $compiler->compileExpression($valueNode),
            ...\array_map(
                $compiler->compileExpression(...),
                $positional,
            ),
        ];

        $named = \array_map(
            $compiler->compileExpression(...),
            $named,
        );

        if ($metadata->contextParameterIndex !== null) {
            $context = 'new \\Tuxxedo\\View\\Lumi\\Runtime\\RuntimeContext($this)';

            if (
                $metadata->contextParameterIndex <= \sizeof($positional) ||
                $metadata->contextParameterName === null
            ) {
                $positional = [
                    ...\array_slice($positional, 0, $metadata->contextParameterIndex),
                    $context,
                    ...\array_slice($positional, $metadata->contextParameterIndex),
                ];
            } else {
                $named[$metadata->contextParameterName] = $context;
            }
        }

        return \sprintf(
            '$this->resolveInstance(\\%s::class)->%s(%s)',
            $metadata->className,
            $metadata->methodName,
            $this->joinCallArguments($positional, $named),
        );
    }

    /**
     * @param array<int|string, ExpressionNodeInterface> $argumentNodes
     *
     * @throws CompilerException
     */
    private function emitPhpFilterCall(
        CompilerInterface $compiler,
        PhpFunctionInterface $handler,
        ExpressionNodeInterface $valueNode,
        array $argumentNodes = [],
    ): string {
        $function = $handler->mappedName ?? $handler->name;

        if (!\function_exists($function)) {
            // @codeCoverageIgnoreStart
            throw CompilerException::fromUnknownFilterFunction(
                filter: $handler->name,
                function: $function,
            );
            // @codeCoverageIgnoreEnd
        }

        [$positional, $named] = $this->splitArguments($argumentNodes);

        $reflector = new \ReflectionFunction($function);
        $given = 1 + \sizeof($positional);

        if ($named !== []) {
            $this->validateNamedArguments(
                kind: 'Filter',
                callable: $handler->name,
                parameters: CallableParameter::listFromReflection($reflector),
                positionalCount: $given,
                names: \array_keys($named),
            );
        } else {
            $minimum = $reflector->getNumberOfRequiredParameters();
            $maximum = $reflector->isVariadic()
                ? null
                : $reflector->getNumberOfParameters();

            if (
                $given < $minimum ||
                (
                    $maximum !== null &&
                    $given > $maximum
                )
            ) {
                throw CompilerException::fromFilterArgumentCount(
                    filter: $handler->name,
                    given: $given,
                    minimum: $minimum,
                    maximum: $maximum,
                );
            }
        }

        return \sprintf(
            '\\%s(%s)',
            $function,
            $this->joinCallArguments(
                [
                    $compiler->compileExpression($valueNode),
                    ...\array_map(
                        $compiler->compileExpression(...),
                        $positional,
                    ),
                ],
                \array_map(
                    $compiler->compileExpression(...),
                    $named,
                ),
            ),
        );
    }

    /**
     * @param array<int|string, ExpressionNodeInterface> $arguments
     * @return array{0: list<ExpressionNodeInterface>, 1: array<string, ExpressionNodeInterface>}
     */
    private function splitArguments(
        array $arguments,
    ): array {
        $positional = [];
        $named = [];

        foreach ($arguments as $name => $argument) {
            if (\is_int($name)) {
                $positional[] = $argument;
            } else {
                $named[$name] = $argument;
            }
        }

        return [
            $positional,
            $named,
        ];
    }

    /**
     * @param list<string> $positional
     * @param array<string, string> $named
     */
    private function joinCallArguments(
        array $positional,
        array $named,
    ): string {
        foreach ($named as $name => $argument) {
            $positional[] = $name . ': ' . $argument;
        }

        return \join(', ', $positional);
    }

    /**
     * @param list<string> $positional
     * @param array<string, string> $named
     */
    private function joinArrayArguments(
        CompilerInterface $compiler,
        array $positional,
        array $named,
    ): string {
        foreach ($named as $name => $argument) {
            $positional[] = '\'' . $compiler->escaper->js($name) . '\' => ' . $argument;
        }

        return \join(', ', $positional);
    }

    /**
     * @param list<CallableParameterInterface> $parameters
     * @param list<string> $names
     *
     * @throws CompilerException
     */
    private function validateNamedArguments(
        string $kind,
        string $callable,
        array $parameters,
        int $positionalCount,
        array $names,
    ): void {
        $variadic = false;
        $byName = [];

        foreach ($parameters as $parameter) {
            if ($parameter->variadic) {
                $variadic = true;

                continue;
            }

            $byName[$parameter->name] = $parameter;
        }

        if (!$variadic && $positionalCount > \sizeof($byName)) {
            throw CompilerException::fromTooManyPositionalArguments(
                kind: $kind,
                callable: $callable,
                given: $positionalCount,
                maximum: \sizeof($byName),
            );
        }

        foreach ($names as $name) {
            $parameter = $byName[$name] ?? null;

            if ($parameter === null) {
                if ($variadic) {
                    continue;
                }

                throw CompilerException::fromUnknownNamedArgument(
                    kind: $kind,
                    callable: $callable,
                    name: $name,
                );
            }

            if ($parameter->position < $positionalCount) {
                throw CompilerException::fromNamedArgumentAlreadyPassed(
                    kind: $kind,
                    callable: $callable,
                    name: $name,
                );
            }
        }

        foreach ($byName as $parameter) {
            if (
                !$parameter->optional &&
                $parameter->position >= $positionalCount &&
                !\in_array($parameter->name, $names, true)
            ) {
                throw CompilerException::fromMissingArgument(
                    kind: $kind,
                    callable: $callable,
                    name: $parameter->name,
                );
            }
        }
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

        yield new NodeCompilerHandler(
            nodeClassName: FilterCallNode::class,
            handler: $this->compileFilterCall(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: TernaryNode::class,
            handler: $this->compileTernary(...),
        );
    }
}
