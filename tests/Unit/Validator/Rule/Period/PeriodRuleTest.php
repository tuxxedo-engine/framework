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

namespace Unit\Validator\Rule\Period;

use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\Period;
use Tuxxedo\Temporal\PeriodInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Period\PeriodRule;
use Tuxxedo\Validator\Rule\Period\PeriodViolationCode;
use Tuxxedo\Validator\Rule\Period\PeriodViolationContext;

class PeriodRuleTest extends TestCase
{
    use RuleTestingTrait;

    public function testNullSkipped(): void
    {
        $result = $this->runRule(
            rule: new PeriodRule(),
            value: null,
        );

        self::assertNull($result);
    }

    public function testValidPeriodPasses(): void
    {
        $period = Period::of(
            start: Instant::parse(input: '2026-01-01T00:00:00Z'),
            end: Instant::parse(input: '2026-06-01T00:00:00Z'),
        );

        $result = $this->runRule(
            rule: new PeriodRule(),
            value: $period,
        );

        self::assertNull($result);
    }

    public function testInvertedHandRolledPeriodFails(): void
    {
        $period = $this->invertedPeriod();

        $result = $this->runRule(
            rule: new PeriodRule(),
            value: $period,
        );

        self::assertNotNull($result);
        self::assertSame(PeriodViolationCode::INVERTED, $result->code);
        self::assertInstanceOf(PeriodViolationContext::class, $result->context);
        self::assertSame('2026-06-01T00:00:00+00:00', $result->context->start);
        self::assertSame('2026-01-01T00:00:00+00:00', $result->context->end);
    }

    public function testNonPeriodInputRaisesWrongType(): void
    {
        $result = $this->runRule(
            rule: new PeriodRule(),
            value: 'not a period',
        );

        self::assertNotNull($result);
        self::assertSame(CommonViolationCode::WRONG_TYPE, $result->code);
    }

    private function invertedPeriod(): PeriodInterface
    {
        return new class (
            start: Instant::parse(input: '2026-06-01T00:00:00Z'),
            end: Instant::parse(input: '2026-01-01T00:00:00Z'),
        ) implements PeriodInterface {
            public function __construct(
                public readonly InstantInterface $start,
                public readonly InstantInterface $end,
            ) {
            }

            public function contains(
                InstantInterface $instant,
            ): bool {
                return false;
            }

            public function overlaps(
                PeriodInterface $other,
            ): bool {
                return false;
            }

            public function length(): DurationInterface
            {
                return Duration::fromSeconds(seconds: 0);
            }
        };
    }
}
