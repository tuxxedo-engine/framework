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

namespace Unit\Temporal;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Temporal\NegotiatingTimeZoneResolver;
use Tuxxedo\Temporal\SourcePriority;
use Tuxxedo\Temporal\SystemDefaultTimeZoneSource;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Temporal\TimeZoneInterface;
use Tuxxedo\Temporal\TimeZoneSourceInterface;

class NegotiatingTimeZoneResolverTest extends TestCase
{
    public function testReturnsFirstSourceThatProducesValue(): void
    {
        $userZone = TimeZone::parse(input: 'Europe/Copenhagen');
        $sessionZone = TimeZone::parse(input: 'America/New_York');

        $resolver = new NegotiatingTimeZoneResolver(
            sources: [
                $this->source(priority: SourcePriority::NORMAL, value: $sessionZone),
                $this->source(priority: SourcePriority::HIGHEST, value: $userZone),
            ],
            fallback: TimeZone::utc(),
        );

        self::assertSame($userZone, $resolver->currentTimeZone());
    }

    public function testSkipsSourcesThatReturnNull(): void
    {
        $zone = TimeZone::parse(input: 'Europe/Copenhagen');

        $resolver = new NegotiatingTimeZoneResolver(
            sources: [
                $this->source(priority: SourcePriority::HIGHEST, value: null),
                $this->source(priority: SourcePriority::NORMAL, value: $zone),
            ],
            fallback: TimeZone::utc(),
        );

        self::assertSame($zone, $resolver->currentTimeZone());
    }

    public function testReturnsFallbackWhenAllSourcesReturnNull(): void
    {
        $fallback = TimeZone::utc();

        $resolver = new NegotiatingTimeZoneResolver(
            sources: [
                $this->source(priority: SourcePriority::HIGH, value: null),
                $this->source(priority: SourcePriority::LOW, value: null),
            ],
            fallback: $fallback,
        );

        self::assertSame($fallback, $resolver->currentTimeZone());
    }

    public function testReturnsFallbackWhenSourceListEmpty(): void
    {
        $fallback = TimeZone::utc();

        $resolver = new NegotiatingTimeZoneResolver(
            sources: [],
            fallback: $fallback,
        );

        self::assertSame($fallback, $resolver->currentTimeZone());
    }

    public function testSystemDefaultSourceParticipatesAsLowest(): void
    {
        $systemZone = TimeZone::parse(input: 'Europe/Copenhagen');
        $userZone = TimeZone::parse(input: 'America/New_York');

        $resolver = new NegotiatingTimeZoneResolver(
            sources: [
                new SystemDefaultTimeZoneSource(default: $systemZone),
                $this->source(priority: SourcePriority::HIGHEST, value: $userZone),
            ],
            fallback: TimeZone::utc(),
        );

        self::assertSame($userZone, $resolver->currentTimeZone());
    }

    private function source(
        SourcePriority $priority,
        ?TimeZoneInterface $value,
    ): TimeZoneSourceInterface {
        return new class ($priority, $value) implements TimeZoneSourceInterface {
            public function __construct(
                public readonly SourcePriority $priority,
                private readonly ?TimeZoneInterface $value,
            ) {
            }

            public function detect(): ?TimeZoneInterface
            {
                return $this->value;
            }
        };
    }
}
