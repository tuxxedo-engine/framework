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

namespace Unit\View\Lumi\Compiler;

use Fixture\View\Lumi\Compiler\Compiler\FooNode;
use Fixture\View\Lumi\Compiler\Compiler\OutOfScopeNode;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerState;
use Tuxxedo\View\Lumi\Compiler\CompilerStateFlag;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

class CompilerStateTest extends TestCase
{
    private CompilerState $state;

    protected function setUp(): void
    {
        $this->state = new CompilerState();
    }

    public function testFreshStateHasNullExpectsAndZeroFlags(): void
    {
        self::assertNull($this->state->expects);
        self::assertSame(0, $this->state->flags);
    }

    public function testHasFlagReturnsFalseWhenFlagNotSet(): void
    {
        self::assertFalse(
            $this->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS),
        );
    }

    public function testFlagSetsFlagAndHasFlagReturnsTrue(): void
    {
        $this->state->flag(CompilerStateFlag::NULL_SAFE_ACCESS);

        self::assertTrue(
            $this->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS),
        );
    }

    public function testRemoveFlagClearsFlag(): void
    {
        $this->state->flag(CompilerStateFlag::NULL_SAFE_ACCESS);
        $this->state->removeFlag(CompilerStateFlag::NULL_SAFE_ACCESS);

        self::assertFalse(
            $this->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS),
        );
    }

    public function testRemoveFlagPreservesUnrelatedFlagState(): void
    {
        $this->state->removeFlag(CompilerStateFlag::NULL_SAFE_ACCESS);

        self::assertFalse(
            $this->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS),
        );
    }

    public function testIsReturnsTrueForCurrentScope(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        self::assertTrue($this->state->is(NodeScope::STATEMENT));
    }

    public function testIsReturnsFalseForDifferentScope(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        self::assertFalse($this->state->is(NodeScope::BLOCK));
    }

    public function testEnterSetsExpects(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        self::assertSame(NodeScope::STATEMENT, $this->state->expects);
    }

    public function testEnterThrowsWhenAlreadyEntered(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        self::expectException(CompilerException::class);

        $this->state->enter(NodeScope::BLOCK);
    }

    public function testLeaveResetsExpectsToNull(): void
    {
        $this->state->enter(NodeScope::STATEMENT);
        $this->state->leave(NodeScope::STATEMENT);

        self::assertNull($this->state->expects);
    }

    public function testLeaveThrowsWhenNotEntered(): void
    {
        self::expectException(CompilerException::class);

        $this->state->leave(NodeScope::STATEMENT);
    }

    public function testSwapReturnsOldScopeAndSetsNewScope(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        $oldScope = $this->state->swap(NodeScope::BLOCK);

        self::assertSame(NodeScope::STATEMENT, $oldScope);
        self::assertSame(NodeScope::BLOCK, $this->state->expects);
    }

    public function testSwapThrowsWhenNotEntered(): void
    {
        self::expectException(CompilerException::class);

        $this->state->swap(NodeScope::STATEMENT);
    }

    public function testValidReturnsTrueWhenNodeAcceptsCurrentScope(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        self::assertTrue(
            $this->state->valid(new FooNode()),
        );
    }

    public function testValidReturnsFalseWhenNodeRejectsCurrentScope(): void
    {
        $this->state->enter(NodeScope::STATEMENT);

        self::assertFalse(
            $this->state->valid(new OutOfScopeNode()),
        );
    }

    public function testDirectivesAreInitializedWithDefaults(): void
    {
        self::assertTrue(
            $this->state->directives->asBool('lumi.autoescape'),
        );
    }
}
