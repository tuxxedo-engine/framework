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

namespace Unit\View\Lumi\Parser;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserState;

class ParserStateTest extends TestCase
{
    private ParserState $state;

    protected function setUp(): void
    {
        $this->state = new ParserState();
    }

    public function testEnterLoopIncrementsDepth(): void
    {
        $this->state->enterLoop();

        self::assertSame(1, $this->state->loopDepth);

        $this->state->enterLoop();

        self::assertSame(2, $this->state->loopDepth);
    }

    public function testLeaveLoopDecrementsDepth(): void
    {
        $this->state->enterLoop();
        $this->state->enterLoop();
        $this->state->leaveLoop();

        self::assertSame(1, $this->state->loopDepth);
    }

    public function testLeaveLoopThrowsWhenAtZeroDepth(): void
    {
        self::expectException(ParserException::class);

        $this->state->leaveLoop();
    }

    public function testEnterConditionIncrementsDepth(): void
    {
        $this->state->enterCondition();

        self::assertSame(1, $this->state->conditionDepth);

        $this->state->enterCondition();

        self::assertSame(2, $this->state->conditionDepth);
    }

    public function testLeaveConditionDecrementsDepth(): void
    {
        $this->state->enterCondition();
        $this->state->enterCondition();
        $this->state->leaveCondition();

        self::assertSame(1, $this->state->conditionDepth);
    }

    public function testLeaveConditionThrowsWhenAtZeroDepth(): void
    {
        self::expectException(ParserException::class);

        $this->state->leaveCondition();
    }

    public function testPushStateSnapshotsAndResets(): void
    {
        $this->state->enterCondition();
        $this->state->set(
            key: 'flag',
            value: true,
        );

        $this->state->pushState();

        self::assertCount(1, $this->state->stateStack);
        self::assertSame(0, $this->state->conditionDepth);
        self::assertSame([], $this->state->state);
    }

    public function testPopStateRestoresSnapshot(): void
    {
        $this->state->enterCondition();
        $this->state->set(
            key: 'flag',
            value: true,
        );

        $this->state->pushState();

        $this->state->set(
            key: 'inner',
            value: 'overlay',
        );
        $this->state->enterCondition();

        $this->state->popState();

        self::assertSame([], $this->state->stateStack);
        self::assertSame(1, $this->state->conditionDepth);
        self::assertTrue($this->state->has('flag'));
        self::assertFalse($this->state->has('inner'));
    }

    public function testPopStateThrowsWhenStackIsEmpty(): void
    {
        self::expectException(ParserException::class);

        $this->state->popState();
    }

    public function testSetReturnsSelfForChaining(): void
    {
        self::assertSame(
            $this->state,
            $this->state->set(
                key: 'flag',
                value: true,
            ),
        );
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse($this->state->has('missing'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->state->set(
            key: 'flag',
            value: true,
        );

        self::assertTrue($this->state->has('flag'));
    }

    public function testClearRemovesKey(): void
    {
        $this->state->set(
            key: 'flag',
            value: true,
        );

        $this->state->clear('flag');

        self::assertFalse($this->state->has('flag'));
    }

    public function testClearReturnsSelfForChaining(): void
    {
        $this->state->set(
            key: 'flag',
            value: true,
        );

        self::assertSame(
            $this->state,
            $this->state->clear('flag'),
        );
    }

    public function testGetStringReturnsValue(): void
    {
        $this->state->set(
            key: 'name',
            value: 'engine',
        );

        self::assertSame('engine', $this->state->getString('name'));
    }

    public function testGetStringThrowsOnMissingKey(): void
    {
        self::expectException(ParserException::class);

        $this->state->getString('missing');
    }

    public function testGetStringThrowsOnNonStringType(): void
    {
        $this->state->set(
            key: 'count',
            value: 5,
        );

        self::expectException(ParserException::class);

        $this->state->getString('count');
    }

    public function testGetIntReturnsValue(): void
    {
        $this->state->set(
            key: 'count',
            value: 42,
        );

        self::assertSame(42, $this->state->getInt('count'));
    }

    public function testGetIntThrowsOnMissingKey(): void
    {
        self::expectException(ParserException::class);

        $this->state->getInt('missing');
    }

    public function testGetIntThrowsOnNonIntType(): void
    {
        $this->state->set(
            key: 'name',
            value: 'engine',
        );

        self::expectException(ParserException::class);

        $this->state->getInt('name');
    }

    public function testGetBoolReturnsValue(): void
    {
        $this->state->set(
            key: 'flag',
            value: true,
        );

        self::assertTrue($this->state->getBool('flag'));
    }

    public function testGetBoolThrowsOnMissingKey(): void
    {
        self::expectException(ParserException::class);

        $this->state->getBool('missing');
    }

    public function testGetBoolThrowsOnNonBoolType(): void
    {
        $this->state->set(
            key: 'count',
            value: 5,
        );

        self::expectException(ParserException::class);

        $this->state->getBool('count');
    }

    public function testIsCleanStateReturnsTrueOnFreshState(): void
    {
        self::assertTrue($this->state->isCleanState());
    }

    public function testIsCleanStateReturnsFalseWhenLoopActive(): void
    {
        $this->state->enterLoop();

        self::assertFalse($this->state->isCleanState());
    }

    public function testIsCleanStateReturnsFalseWhenConditionActive(): void
    {
        $this->state->enterCondition();

        self::assertFalse($this->state->isCleanState());
    }

    public function testIsCleanStateReturnsFalseWhenCustomStateSet(): void
    {
        $this->state->set(
            key: 'flag',
            value: true,
        );

        self::assertFalse($this->state->isCleanState());
    }

    public function testIsCleanStateIgnoresLoopWhenCheckLoopsDisabled(): void
    {
        $this->state->enterLoop();

        self::assertTrue(
            $this->state->isCleanState(
                checkLoops: false,
            ),
        );
    }

    public function testIsCleanStateIgnoresConditionWhenCheckConditionsDisabled(): void
    {
        $this->state->enterCondition();

        self::assertTrue(
            $this->state->isCleanState(
                checkConditions: false,
            ),
        );
    }

    public function testIsCleanStateIgnoresCustomWhenCheckCustomDisabled(): void
    {
        $this->state->set(
            key: 'flag',
            value: true,
        );

        self::assertTrue(
            $this->state->isCleanState(
                checkCustom: false,
            ),
        );
    }
}
