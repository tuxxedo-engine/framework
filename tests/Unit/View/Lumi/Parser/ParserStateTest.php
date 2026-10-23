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

    public function testFreshStateHasNoDepth(): void
    {
        self::assertSame(0, $this->state->loopDepth);
        self::assertSame(0, $this->state->nestingDepth);
    }

    public function testEnterNestingIncrementsDepth(): void
    {
        $this->state->enterNesting();

        self::assertSame(1, $this->state->nestingDepth);

        $this->state->enterNesting();

        self::assertSame(2, $this->state->nestingDepth);
    }

    public function testLeaveNestingDecrementsDepth(): void
    {
        $this->state->enterNesting();
        $this->state->enterNesting();
        $this->state->leaveNesting();

        self::assertSame(1, $this->state->nestingDepth);
    }

    public function testLeaveNestingThrowsWhenAtZeroDepth(): void
    {
        self::expectException(ParserException::class);

        $this->state->leaveNesting();
    }

    public function testLoopAndNestingDepthsAreIndependent(): void
    {
        $this->state->enterLoop();
        $this->state->enterNesting();
        $this->state->enterNesting();
        $this->state->leaveLoop();

        self::assertSame(0, $this->state->loopDepth);
        self::assertSame(2, $this->state->nestingDepth);
    }
}
