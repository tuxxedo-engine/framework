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

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Scope\Lattice;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

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
