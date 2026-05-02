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

namespace Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\WeightedHeader;
use Tuxxedo\Http\WeightedHeaderPair;

class WeightedHeaderTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: string[]}>
     */
    public static function weightedOrderDataProvider(): \Generator
    {
        yield [
            'text/html',
            [
                'text/html',
            ],
        ];

        yield [
            'text/html, application/json',
            [
                'text/html',
                'application/json',
            ],
        ];

        yield [
            'text/html;q=0.5, application/json;q=0.9',
            [
                'application/json',
                'text/html',
            ],
        ];

        yield [
            'text/html;q=0.5, application/json;q=0.5',
            [
                'text/html',
                'application/json',
            ],
        ];

        yield [
            ' text/html , application/json ',
            [
                'text/html',
                'application/json',
            ],
        ];

        yield [
            'text/html;q=abc, application/json;q=0.9',
            [
                'text/html',
                'application/json',
            ],
        ];

        yield [
            '"text/html"',
            [
                'text/html',
            ],
        ];
    }

    /**
     * @param string[] $expected
     */
    #[DataProvider('weightedOrderDataProvider')]
    public function testGetWeightedOrder(
        string $value,
        array $expected,
    ): void {
        $header = new WeightedHeader('Accept', $value);

        self::assertSame($expected, \array_values($header->getWeightedOrder()));
    }

    public function testGetWeightedPairsSingleValue(): void
    {
        $header = new WeightedHeader('Accept', 'text/html');
        $pairs = $header->getWeightedPairs();

        self::assertCount(1, $pairs);
        self::assertInstanceOf(WeightedHeaderPair::class, $pairs[0]);
        self::assertSame('text/html', $pairs[0]->value);
        self::assertSame(1.0, $pairs[0]->weight);
    }

    public function testGetWeightedPairsExplicitWeight(): void
    {
        $header = new WeightedHeader('Accept', 'text/html;q=0.8');
        $pairs = $header->getWeightedPairs();

        self::assertCount(1, $pairs);
        self::assertSame('text/html', $pairs[0]->value);
        self::assertSame(0.8, $pairs[0]->weight);
    }

    public function testGetWeightedPairsSortedByWeight(): void
    {
        $header = new WeightedHeader('Accept', 'text/html;q=0.5, application/json;q=0.9');
        $pairs = $header->getWeightedPairs();

        self::assertCount(2, $pairs);
        self::assertSame('application/json', $pairs[0]->value);
        self::assertSame(0.9, $pairs[0]->weight);
        self::assertSame('text/html', $pairs[1]->value);
        self::assertSame(0.5, $pairs[1]->weight);
    }

    public function testGetWeightedPairsEqualWeightsPreserveOrder(): void
    {
        $header = new WeightedHeader('Accept', 'text/html;q=0.5, application/json;q=0.5');
        $pairs = $header->getWeightedPairs();

        self::assertCount(2, $pairs);
        self::assertSame('text/html', $pairs[0]->value);
        self::assertSame('application/json', $pairs[1]->value);
    }
}
