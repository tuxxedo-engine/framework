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
use Tuxxedo\View\Lumi\Optimizer\Scope\Variable;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;

class VariableTest extends TestCase
{
    private function makeScope(): Scope
    {
        return new Scope(
            evaluator: new Evaluator(),
        );
    }

    private function assignNode(
        string $name,
        LiteralNode|IdentifierNode|BinaryOpNode $value,
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

    public function testFromNewAssignSetsName(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        self::assertSame('foo', $variable->name);
    }

    public function testFromNewAssignSetsScope(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        self::assertSame($scope, $variable->scope);
    }

    public function testFromNewAssignWithLiteralProducesConstLattice(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(42),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        self::assertSame(Lattice::CONST, $variable->lattice);
    }

    public function testFromNewAssignWithLiteralStoresComputedValue(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(42),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        self::assertSame(42, $variable->computedValue);
    }

    public function testFromNewAssignStoresValueNode(): void
    {
        $scope = $this->makeScope();
        $literal = LiteralNode::createInt(42);

        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: $literal,
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        self::assertSame($literal, $variable->value);
    }

    public function testFromUndefinedSetsName(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromUndefined(
            scope: $scope,
            name: 'missing',
        );

        self::assertSame('missing', $variable->name);
    }

    public function testFromUndefinedSetsUndefLattice(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromUndefined(
            scope: $scope,
            name: 'missing',
        );

        self::assertSame(Lattice::UNDEF, $variable->lattice);
    }

    public function testFromUndefinedHasNullValue(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromUndefined(
            scope: $scope,
            name: 'missing',
        );

        self::assertNull($variable->value);
    }

    public function testFromVaryingSetsName(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'x',
        );

        self::assertSame('x', $variable->name);
    }

    public function testFromVaryingSetsVaryingLattice(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'x',
        );

        self::assertSame(Lattice::VARYING, $variable->lattice);
    }

    public function testFromExistingProducesVaryingLattice(): void
    {
        $scope = $this->makeScope();
        $original = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        $newVariable = Variable::fromExisting(
            scope: $scope,
            variable: $original,
        );

        self::assertSame(Lattice::VARYING, $newVariable->lattice);
    }

    public function testFromExistingPreservesName(): void
    {
        $scope = $this->makeScope();
        $original = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        $newVariable = Variable::fromExisting(
            scope: $scope,
            variable: $original,
        );

        self::assertSame('foo', $newVariable->name);
    }

    public function testFromExistingHasNullValue(): void
    {
        $scope = $this->makeScope();
        $original = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        $newVariable = Variable::fromExisting(
            scope: $scope,
            variable: $original,
        );

        self::assertNull($newVariable->value);
    }

    public function testHasComputedValueReturnsTrueForLiteralAssignment(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(7),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        self::assertTrue($variable->hasComputedValue());
    }

    public function testHasComputedValueReturnsFalseForUndefined(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromUndefined(
            scope: $scope,
            name: 'missing',
        );

        self::assertFalse($variable->hasComputedValue());
    }

    public function testHasComputedValueReturnsFalseForVarying(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'x',
        );

        self::assertFalse($variable->hasComputedValue());
    }

    public function testMutateWithLiteralAndAssignOperatorReplacesComputedValue(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromNewAssign(
            scope: $scope,
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(1),
            ),
            name: new IdentifierNode(
                name: 'foo',
            ),
        );

        $variable->mutate(
            scope: $scope,
            value: LiteralNode::createInt(2),
        );

        self::assertSame(2, $variable->computedValue);
    }

    public function testMutateWithLiteralAndCompoundAddComputesNewValue(): void
    {
        $scope = $this->makeScope();
        $scope->assign(
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createInt(10),
            ),
        );

        $variable = $scope->variables['foo'];
        $variable->mutate(
            scope: $scope,
            value: LiteralNode::createInt(5),
            operator: AssignmentSymbol::ADD,
        );

        self::assertSame(15, $variable->computedValue);
        self::assertSame(Lattice::CONST, $variable->lattice);
    }

    public function testMutateWithIdenticalIdentifierValueIsNoOp(): void
    {
        $scope = $this->makeScope();
        $scope->assign(
            node: $this->assignNode(
                name: 'other',
                value: LiteralNode::createInt(99),
            ),
        );
        $scope->assign(
            node: $this->assignNode(
                name: 'foo',
                value: new IdentifierNode(
                    name: 'other',
                ),
            ),
        );

        $variable = $scope->variables['foo'];
        $valueBefore = $variable->value;
        $latticeBefore = $variable->lattice;

        $variable->mutate(
            scope: $scope,
            value: new IdentifierNode(
                name: 'other',
            ),
        );

        self::assertSame($valueBefore, $variable->value);
        self::assertSame($latticeBefore, $variable->lattice);
    }

    public function testMutateWithIdentifierResolvingToLiteralBecomesConst(): void
    {
        $scope = $this->makeScope();
        $scope->assign(
            node: $this->assignNode(
                name: 'src',
                value: LiteralNode::createInt(123),
            ),
        );

        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'dst',
        );

        $variable->mutate(
            scope: $scope,
            value: new IdentifierNode(
                name: 'src',
            ),
        );

        self::assertSame(Lattice::CONST, $variable->lattice);
        self::assertSame(123, $variable->computedValue);
    }

    public function testMutateWithIdentifierResolvingToUndefinedBecomesVarying(): void
    {
        $scope = $this->makeScope();

        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'dst',
        );

        $variable->mutate(
            scope: $scope,
            value: new IdentifierNode(
                name: 'missing',
            ),
        );

        self::assertSame(Lattice::VARYING, $variable->lattice);
    }

    public function testMutateWithBinaryOpOfLiteralsBecomesConst(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'result',
        );

        $variable->mutate(
            scope: $scope,
            value: new BinaryOpNode(
                left: LiteralNode::createInt(2),
                right: LiteralNode::createInt(3),
                operator: BinarySymbol::ADD,
            ),
        );

        self::assertSame(Lattice::CONST, $variable->lattice);
        self::assertSame(5, $variable->computedValue);
    }

    public function testMutateWithCompoundAddOnUnsetPreviousValueSkipsComputation(): void
    {
        $scope = $this->makeScope();
        $variable = Variable::fromVarying(
            scope: $scope,
            name: 'x',
        );

        $variable->mutate(
            scope: $scope,
            value: LiteralNode::createInt(7),
            operator: AssignmentSymbol::ADD,
        );

        self::assertSame(Lattice::CONST, $variable->lattice);
        self::assertSame(7, $variable->computedValue);
    }

    public function testMutateWithCompoundOperatorReturningNonLiteralBecomesVarying(): void
    {
        $scope = $this->makeScope();
        $scope->assign(
            node: $this->assignNode(
                name: 'foo',
                value: LiteralNode::createString('hello'),
            ),
        );

        $variable = $scope->variables['foo'];
        $variable->mutate(
            scope: $scope,
            value: LiteralNode::createString(' world'),
            operator: AssignmentSymbol::ADD,
        );

        self::assertSame(Lattice::VARYING, $variable->lattice);
    }
}
