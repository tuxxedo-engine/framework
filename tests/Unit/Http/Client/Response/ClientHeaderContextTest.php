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

namespace Unit\Http\Client\Response;

use Fixture\Http\Request\Context\InputContextEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Response\ClientHeaderContext;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\WeightedHeader;

class ClientHeaderContextTest extends TestCase
{
    private function makeContext(): ClientHeaderContext
    {
        return new ClientHeaderContext(
            [
                new Header('content-type', 'application/json'),
                new Header('Link', '<https://api.example/p2>; rel="next"'),
                new Header('Link', '<https://api.example/p9>; rel="last"'),
                new Header('Accept-Language', 'da;q=0.5, en'),
                new Header('X-RateLimit-Remaining', '42'),
                new Header('X-Ratio', '0.75'),
                new Header('X-Enabled', '1'),
                new Header('X-Choice', 'bar'),
            ],
        );
    }

    public function testAllReturnsEveryHeaderInReceivedOrder(): void
    {
        $headers = $this->makeContext()->all();

        self::assertCount(8, $headers);
        self::assertSame('content-type', $headers[0]->name);
        self::assertSame('<https://api.example/p2>; rel="next"', $headers[1]->value);
        self::assertSame('<https://api.example/p9>; rel="last"', $headers[2]->value);
    }

    public function testAllIsEmptyWithoutHeaders(): void
    {
        self::assertSame([], new ClientHeaderContext()->all());
    }

    public function testHasIsCaseInsensitive(): void
    {
        $context = $this->makeContext();

        self::assertTrue($context->has('Content-Type'));
        self::assertTrue($context->has('CONTENT-TYPE'));
        self::assertTrue($context->has('link'));
        self::assertFalse($context->has('X-Missing'));
    }

    public function testGetKeepsTheFirstSpellingOfTheName(): void
    {
        self::assertSame('content-type', $this->makeContext()->get('Content-Type')->name);
    }

    public function testGetJoinsRepeatedHeaders(): void
    {
        self::assertSame(
            '<https://api.example/p2>; rel="next", <https://api.example/p9>; rel="last"',
            $this->makeContext()->get('LINK')->value,
        );
    }

    public function testGetReturnsWeightedHeaderForWeightedValues(): void
    {
        self::assertInstanceOf(WeightedHeader::class, $this->makeContext()->get('accept-language'));
    }

    public function testGetThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->get('X-Missing');
    }

    public function testIsWeighted(): void
    {
        $context = $this->makeContext();

        self::assertTrue($context->isWeighted('Accept-Language'));
        self::assertFalse($context->isWeighted('Content-Type'));
        self::assertFalse($context->isWeighted('X-Missing'));
    }

    public function testIsWeightedValueAcceptsHeaders(): void
    {
        $context = $this->makeContext();

        self::assertTrue($context->isWeightedValue(new Header('Accept', 'text/html;q=0.9')));
        self::assertFalse($context->isWeightedValue('text/html'));
    }

    public function testGetWeighted(): void
    {
        self::assertSame(
            [
                'en',
                'da',
            ],
            $this->makeContext()->getWeighted('accept-language')->getWeightedOrder(),
        );
    }

    public function testGetWeightedThrowsForUnweightedHeader(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->getWeighted('Content-Type');
    }

    public function testTypedReaders(): void
    {
        $context = $this->makeContext();

        self::assertSame(42, $context->int('x-ratelimit-remaining'));
        self::assertSame(0.75, $context->float('x-ratio'));
        self::assertTrue($context->bool('x-enabled'));
        self::assertSame('application/json', $context->string('Content-Type'));
    }

    public function testTypedReadersThrowWhenHeaderMissing(): void
    {
        $context = $this->makeContext();

        foreach (
            [
                static fn (): int => $context->int('X-Missing'),
                static fn (): float => $context->float('X-Missing'),
                static fn (): bool => $context->bool('X-Missing'),
                static fn (): string => $context->string('X-Missing'),
            ] as $reader
        ) {
            try {
                $reader();

                self::fail('Expected HttpException was not thrown');
            } catch (HttpException $exception) {
                self::assertSame(500, $exception->responseCode->getStatusCode());
            }
        }
    }

    public function testEnumMatchesCaseInsensitively(): void
    {
        self::assertSame(
            InputContextEnum::BAR,
            $this->makeContext()->enum('X-Choice', InputContextEnum::class),
        );
    }

    public function testEnumThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->enum('X-Missing', InputContextEnum::class);
    }

    public function testEnumThrowsWhenValueDoesNotMatchAnyCase(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->enum('Content-Type', InputContextEnum::class);
    }
}
