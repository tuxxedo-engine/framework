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

namespace Unit\Validator\Rule\Instant;

use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Instant\BeforeInstantRule;
use Tuxxedo\Validator\Rule\Instant\InstantOrderViolationCode;
use Tuxxedo\Validator\Rule\Instant\InstantOrderViolationContext;

class BeforeInstantRuleTest extends TestCase
{
    use RuleTestingTrait;

    public function testNullSkipped(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-01-01T00:00:00Z'),
            value: null,
        );

        self::assertNull($result);
    }

    public function testInstantBeforeAnchorPasses(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-06-01T00:00:00Z'),
            value: Instant::parse(input: '2026-01-01T00:00:00Z'),
        );

        self::assertNull($result);
    }

    public function testInstantAtOrAfterAnchorFails(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-06-01T00:00:00Z'),
            value: Instant::parse(input: '2026-06-01T00:00:00Z'),
        );

        self::assertNotNull($result);
        self::assertSame(InstantOrderViolationCode::NOT_BEFORE, $result->code);
    }

    public function testAnchorInstantCarriedInContext(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(
                anchor: Instant::parse(input: '2026-06-01T00:00:00Z'),
            ),
            value: Instant::parse(input: '2026-12-31T00:00:00Z'),
        );

        self::assertNotNull($result);
        self::assertInstanceOf(InstantOrderViolationContext::class, $result->context);
        self::assertSame('2026-06-01T00:00:00+00:00', $result->context->anchor);
    }

    public function testDateTimeImmutableAcceptedAndConverted(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-06-01T00:00:00Z'),
            value: new \DateTimeImmutable(datetime: '2026-01-01T00:00:00Z'),
        );

        self::assertNull($result);
    }

    public function testStringParseableAcceptedAndConverted(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-06-01T00:00:00Z'),
            value: '2026-01-01T00:00:00Z',
        );

        self::assertNull($result);
    }

    public function testUnparseableStringRaisesWrongType(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-06-01T00:00:00Z'),
            value: 'nope',
        );

        self::assertNotNull($result);
        self::assertSame(CommonViolationCode::WRONG_TYPE, $result->code);
    }

    public function testIntRaisesWrongType(): void
    {
        $result = $this->runRule(
            rule: new BeforeInstantRule(anchor: '2026-06-01T00:00:00Z'),
            value: 42,
        );

        self::assertNotNull($result);
        self::assertSame(CommonViolationCode::WRONG_TYPE, $result->code);
    }
}
