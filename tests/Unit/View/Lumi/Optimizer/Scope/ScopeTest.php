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

namespace Unit\View\Lumi\Optimizer\Scope;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Scope\Lattice;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;

class ScopeTest extends TestCase
{
    private function makeAssignment(
        string $name,
        LiteralNode|IdentifierNode $value,
        AssignmentSymbol $operator = AssignmentSymbol::ASSIGN,
    ): AssignmentNode {
        return new AssignmentNode(
            name: new IdentifierNode(
                name: $name,
            ),
            value: $value,
            operator: $operator,
        );
    }

    public function testConstructorDefaultsEvaluator(): void
    {
        $scope = new Scope();

        self::assertInstanceOf(Evaluator::class, $scope->evaluator);
    }

    public function testConstructorAcceptsExplicitEvaluator(): void
    {
        $evaluator = new Evaluator();
        $scope = new Scope(
            evaluator: $evaluator,
        );

        self::assertSame($evaluator, $scope->evaluator);
    }

    public function testConstructorStartsWithEmptyVariables(): void
    {
        $scope = new Scope();

        self::assertSame([], $scope->variables);
    }

    public function testAssignCreatesNewVariableForFreshName(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createString('bar'),
            ),
        );

        self::assertArrayHasKey('foo', $scope->variables);
    }

    public function testAssignStoresVariableWithCorrectName(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createString('bar'),
            ),
        );

        self::assertSame('foo', $scope->variables['foo']->name);
    }

    public function testAssignWithCompoundOperatorOnUnknownNameCreatesNewVariable(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(5),
                operator: AssignmentSymbol::ADD,
            ),
        );

        self::assertArrayHasKey('foo', $scope->variables);
    }

    public function testAssignWithCompoundOperatorMutatesExistingVariable(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(5),
            ),
        );

        $original = $scope->variables['foo'];

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(3),
                operator: AssignmentSymbol::ADD,
            ),
        );

        self::assertSame($original, $scope->variables['foo']);
    }

    public function testAssignWithCompoundAddOnIntComputesNewValue(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(5),
            ),
        );

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(3),
                operator: AssignmentSymbol::ADD,
            ),
        );

        self::assertSame(8, $scope->variables['foo']->computedValue);
    }

    /**
     * @return \Generator<string, array{0: LiteralNode, 1: list<array{0: AssignmentSymbol, 1: ExpressionNodeInterface}>, 2: int|string}>
     */
    public static function compoundChainDataProvider(): \Generator
    {
        yield 'arithmetic' => [
            LiteralNode::createInt(0),
            [
                [
                    AssignmentSymbol::ADD,
                    LiteralNode::createInt(5),
                ],
                [
                    AssignmentSymbol::SUBTRACT,
                    LiteralNode::createInt(1),
                ],
                [
                    AssignmentSymbol::MULTIPLY,
                    LiteralNode::createInt(3),
                ],
                [
                    AssignmentSymbol::DIVIDE,
                    LiteralNode::createInt(2),
                ],
                [
                    AssignmentSymbol::MODULUS,
                    LiteralNode::createInt(4),
                ],
                [
                    AssignmentSymbol::EXPONENTIATE,
                    LiteralNode::createInt(3),
                ],
            ],
            8,
        ];

        yield 'bitwise' => [
            LiteralNode::createInt(12),
            [
                [
                    AssignmentSymbol::BITWISE_AND,
                    LiteralNode::createInt(10),
                ],
                [
                    AssignmentSymbol::BITWISE_OR,
                    LiteralNode::createInt(1),
                ],
                [
                    AssignmentSymbol::BITWISE_XOR,
                    LiteralNode::createInt(3),
                ],
                [
                    AssignmentSymbol::BITWISE_SHIFT_LEFT,
                    LiteralNode::createInt(2),
                ],
                [
                    AssignmentSymbol::BITWISE_SHIFT_RIGHT,
                    LiteralNode::createInt(1),
                ],
            ],
            20,
        ];

        yield 'concat with expression operand' => [
            LiteralNode::createString('a'),
            [
                [
                    AssignmentSymbol::CONCAT,
                    new BinaryOpNode(
                        left: LiteralNode::createString('b'),
                        right: LiteralNode::createString('c'),
                        operator: BinarySymbol::CONCAT,
                    ),
                ],
                [
                    AssignmentSymbol::CONCAT,
                    LiteralNode::createString('d'),
                ],
            ],
            'abcd',
        ];

        yield 'self reference' => [
            LiteralNode::createInt(3),
            [
                [
                    AssignmentSymbol::ADD,
                    new IdentifierNode(
                        name: 'foo',
                    ),
                ],
                [
                    AssignmentSymbol::MULTIPLY,
                    new IdentifierNode(
                        name: 'foo',
                    ),
                ],
            ],
            36,
        ];
    }

    /**
     * @param list<array{0: AssignmentSymbol, 1: ExpressionNodeInterface}> $steps
     */
    #[DataProvider('compoundChainDataProvider')]
    public function testCompoundAssignmentChainComputesRuntimeValue(
        LiteralNode $initial,
        array $steps,
        int|string $expected,
    ): void {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: $initial,
            ),
        );

        foreach ($steps as [$operator, $value]) {
            $scope->assign(
                node: new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'foo',
                    ),
                    value: $value,
                    operator: $operator,
                ),
            );
        }

        self::assertSame(Lattice::CONST, $scope->variables['foo']->lattice);
        self::assertSame($expected, $scope->variables['foo']->computedValue);
        self::assertInstanceOf(LiteralNode::class, $scope->variables['foo']->value);
    }

    public function testCompoundAssignmentOnUnknownVariableIsVarying(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
                operator: AssignmentSymbol::SUBTRACT,
            ),
        );

        self::assertSame(Lattice::VARYING, $scope->variables['foo']->lattice);
        self::assertFalse($scope->variables['foo']->hasComputedValue());
    }

    public function testCompoundAssignmentOnVaryingVariableStaysVarying(): void
    {
        $scope = new Scope();

        $scope->create('foo');

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
                operator: AssignmentSymbol::ADD,
            ),
        );

        self::assertSame(Lattice::VARYING, $scope->variables['foo']->lattice);
        self::assertFalse($scope->variables['foo']->hasComputedValue());
        self::assertNull($scope->variables['foo']->value);
    }

    public function testCompoundAssignmentWithUnknownOperandIsVarying(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: new IdentifierNode(
                    name: 'unknown',
                ),
                operator: AssignmentSymbol::ADD,
            ),
        );

        self::assertSame(Lattice::VARYING, $scope->variables['foo']->lattice);
        self::assertFalse($scope->variables['foo']->hasComputedValue());
    }

    public function testAliasOfSelfReferencingAssignmentUsesValueAtAssignmentTime(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: $this->makeAssignment(
                name: 'n',
                value: LiteralNode::createInt(4),
            ),
        );

        $scope->assign(
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'n',
                ),
                value: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'n',
                    ),
                    right: LiteralNode::createInt(2),
                    operator: BinarySymbol::ADD,
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        $scope->assign(
            node: $this->makeAssignment(
                name: 'm',
                value: new IdentifierNode(
                    name: 'n',
                ),
            ),
        );

        self::assertSame(6, $scope->variables['n']->computedValue);
        self::assertSame(6, $scope->variables['m']->computedValue);
        self::assertInstanceOf(LiteralNode::class, $scope->variables['n']->value);
    }

    public function testAliasOfVaryingExpressionStaysVaryingAfterOperandBecomesConstant(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'unknown',
                    ),
                    right: LiteralNode::createInt(1),
                    operator: BinarySymbol::ADD,
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        $scope->assign(
            node: $this->makeAssignment(
                name: 'unknown',
                value: LiteralNode::createInt(5),
            ),
        );

        $scope->assign(
            node: $this->makeAssignment(
                name: 'z',
                value: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );

        self::assertSame(Lattice::VARYING, $scope->variables['x']->lattice);
        self::assertNull($scope->variables['x']->value);
        self::assertSame(Lattice::VARYING, $scope->variables['z']->lattice);
        self::assertFalse($scope->variables['z']->hasComputedValue());
    }

    public function testAssignWithNonIdentifierLhsIsNoOp(): void
    {
        $scope = new Scope();

        $scope->assign(
            node: new AssignmentNode(
                name: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'object',
                    ),
                    property: 'field',
                ),
                value: LiteralNode::createInt(1),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame([], $scope->variables);
    }

    public function testCreateAddsVaryingVariableFromString(): void
    {
        $scope = new Scope();

        $scope->create(
            name: 'x',
        );

        self::assertArrayHasKey('x', $scope->variables);
        self::assertSame(Lattice::VARYING, $scope->variables['x']->lattice);
    }

    public function testCreateAddsVariableFromIdentifierNode(): void
    {
        $scope = new Scope();

        $scope->create(
            name: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertArrayHasKey('y', $scope->variables);
    }

    public function testCreateOverwritesExistingVariable(): void
    {
        $scope = new Scope();
        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        $scope->create(
            name: 'foo',
        );

        self::assertSame(Lattice::VARYING, $scope->variables['foo']->lattice);
    }

    public function testGetReturnsExistingVariable(): void
    {
        $scope = new Scope();
        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        $variable = $scope->get(
            name: 'foo',
        );

        self::assertSame($scope->variables['foo'], $variable);
    }

    public function testGetCreatesUndefinedVariableForMissingName(): void
    {
        $scope = new Scope();

        $variable = $scope->get(
            name: 'missing',
        );

        self::assertSame(Lattice::UNDEF, $variable->lattice);
        self::assertArrayHasKey('missing', $scope->variables);
    }

    public function testGetAcceptsIdentifierNode(): void
    {
        $scope = new Scope();

        $variable = $scope->get(
            name: new IdentifierNode(
                name: 'missing',
            ),
        );

        self::assertSame('missing', $variable->name);
    }

    public function testGetCachesUndefinedVariableOnRepeatedCalls(): void
    {
        $scope = new Scope();
        $first = $scope->get(
            name: 'missing',
        );

        $second = $scope->get(
            name: 'missing',
        );

        self::assertSame($first, $second);
    }

    public function testExistsReturnsTrueForAssignedVariable(): void
    {
        $scope = new Scope();
        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        self::assertTrue(
            $scope->exists(
                name: 'foo',
            ),
        );
    }

    public function testExistsReturnsFalseForUnknownVariable(): void
    {
        $scope = new Scope();

        self::assertFalse(
            $scope->exists(
                name: 'missing',
            ),
        );
    }

    public function testExistsAcceptsIdentifierNode(): void
    {
        $scope = new Scope();
        $scope->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        self::assertTrue(
            $scope->exists(
                name: new IdentifierNode(
                    name: 'foo',
                ),
            ),
        );
    }

    public function testMergeReturnsSelf(): void
    {
        $left = new Scope();
        $right = new Scope();

        self::assertSame(
            $left,
            $left->merge(
                scope: $right,
            ),
        );
    }

    public function testMergePromotesSharedVariableToVarying(): void
    {
        $left = new Scope();
        $left->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        $right = new Scope();
        $right->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(2),
            ),
        );

        $left->merge(
            scope: $right,
        );

        self::assertSame(Lattice::VARYING, $left->variables['foo']->lattice);
    }

    public function testMergeIgnoresVariablesNotPresentInTarget(): void
    {
        $left = new Scope();
        $left->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        $right = new Scope();
        $right->assign(
            node: $this->makeAssignment(
                name: 'bar',
                value: LiteralNode::createInt(2),
            ),
        );

        $left->merge(
            scope: $right,
        );

        self::assertArrayNotHasKey('bar', $left->variables);
    }

    public function testMergePreservesVariablesAbsentFromSource(): void
    {
        $left = new Scope();
        $left->assign(
            node: $this->makeAssignment(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
        );

        $right = new Scope();

        $left->merge(
            scope: $right,
        );

        self::assertArrayHasKey('foo', $left->variables);
        self::assertSame(Lattice::CONST, $left->variables['foo']->lattice);
    }
}
