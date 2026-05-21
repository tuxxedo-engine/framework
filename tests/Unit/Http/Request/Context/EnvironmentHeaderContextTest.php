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

namespace Unit\Http\Request\Context;

use Fixture\Http\Request\Context\InputContextEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\EnvironmentHeaderContext;
use Tuxxedo\Http\WeightedHeader;

class EnvironmentHeaderContextTest extends TestCase
{
    /**
     * @var array<mixed>
     */
    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testAllReturnsEmptyArrayWhenNoHeadersPresent(): void
    {
        self::assertSame([], (new EnvironmentHeaderContext())->all());
    }

    public function testAllReturnsCollectedHeaders(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $headers = (new EnvironmentHeaderContext())->all();

        self::assertCount(2, $headers);
        self::assertContainsOnlyInstancesOf(Header::class, $headers);
    }

    public function testAllPromotesWeightedHeaderInstances(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html;q=0.8';

        $headers = (new EnvironmentHeaderContext())->all();

        self::assertCount(1, $headers);
        self::assertInstanceOf(WeightedHeader::class, $headers[0]);
        self::assertSame('Accept', $headers[0]->name);
        self::assertSame('text/html;q=0.8', $headers[0]->value);
    }

    public function testLazyLoadSkipsHttpCookieHeader(): void
    {
        $_SERVER['HTTP_COOKIE'] = 'session=abc';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        $context = new EnvironmentHeaderContext();

        self::assertFalse($context->has('Cookie'));
        self::assertTrue($context->has('User-Agent'));
    }

    public function testLazyLoadSkipsNonScalarValues(): void
    {
        $_SERVER['HTTP_ARRAY_HEADER'] = [
            'a',
            'b',
        ];

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        $context = new EnvironmentHeaderContext();

        self::assertFalse($context->has('Array-Header'));
        self::assertTrue($context->has('User-Agent'));
    }

    public function testLazyLoadSkipsNonHttpServerKeys(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        $context = new EnvironmentHeaderContext();

        self::assertCount(1, $context->all());
        self::assertTrue($context->has('User-Agent'));
    }

    public function testLazyLoadIsCachedAfterFirstCall(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        $context = new EnvironmentHeaderContext();

        self::assertTrue($context->has('User-Agent'));

        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        self::assertFalse($context->has('Accept'));
    }

    public function testHasReturnsTrueWhenHeaderPresent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        self::assertTrue((new EnvironmentHeaderContext())->has('User-Agent'));
    }

    public function testHasReturnsFalseWhenHeaderMissing(): void
    {
        self::assertFalse((new EnvironmentHeaderContext())->has('User-Agent'));
    }

    public function testGetReturnsHeaderInstance(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        $header = (new EnvironmentHeaderContext())->get('User-Agent');

        self::assertInstanceOf(Header::class, $header);
        self::assertNotInstanceOf(WeightedHeader::class, $header);
        self::assertSame('User-Agent', $header->name);
        self::assertSame('Mozilla', $header->value);
    }

    public function testGetReturnsWeightedHeaderInstanceForWeightedValue(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html;q=0.9';

        $header = (new EnvironmentHeaderContext())->get('Accept');

        self::assertInstanceOf(WeightedHeader::class, $header);
        self::assertSame('Accept', $header->name);
        self::assertSame('text/html;q=0.9', $header->value);
    }

    public function testGetThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->get('User-Agent');
    }

    public function testIsWeightedReturnsTrueForWeightedHeader(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html;q=0.8';

        self::assertTrue((new EnvironmentHeaderContext())->isWeighted('Accept'));
    }

    public function testIsWeightedReturnsFalseForNonWeightedHeader(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        self::assertFalse((new EnvironmentHeaderContext())->isWeighted('User-Agent'));
    }

    public function testIsWeightedReturnsFalseWhenHeaderMissing(): void
    {
        self::assertFalse((new EnvironmentHeaderContext())->isWeighted('Accept'));
    }

    public function testIsWeightedValueAcceptsString(): void
    {
        $context = new EnvironmentHeaderContext();

        self::assertTrue($context->isWeightedValue('text/html;q=0.8'));
        self::assertFalse($context->isWeightedValue('text/html'));
    }

    public function testIsWeightedValueAcceptsQuotedWeight(): void
    {
        self::assertTrue(
            (new EnvironmentHeaderContext())->isWeightedValue('text/html;q="0.8"'),
        );
    }

    public function testIsWeightedValueAcceptsVersionParameter(): void
    {
        self::assertTrue(
            (new EnvironmentHeaderContext())->isWeightedValue('application/json;v=1.0'),
        );
    }

    public function testIsWeightedValueAcceptsHeaderInstance(): void
    {
        $context = new EnvironmentHeaderContext();

        self::assertFalse(
            $context->isWeightedValue(
                new Header(
                    name: 'User-Agent',
                    value: 'Mozilla',
                ),
            ),
        );
    }

    public function testIsWeightedValueAcceptsWeightedHeaderInstance(): void
    {
        $context = new EnvironmentHeaderContext();

        self::assertTrue(
            $context->isWeightedValue(
                new WeightedHeader(
                    name: 'Accept',
                    value: 'text/html;q=0.8',
                ),
            ),
        );
    }

    public function testGetWeightedReturnsWeightedHeader(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html;q=0.9';

        $header = (new EnvironmentHeaderContext())->getWeighted('Accept');

        self::assertInstanceOf(WeightedHeader::class, $header);
        self::assertSame('Accept', $header->name);
        self::assertSame('text/html;q=0.9', $header->value);
    }

    public function testGetWeightedThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getWeighted('Accept');
    }

    public function testGetWeightedThrowsWhenHeaderIsNotWeighted(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getWeighted('User-Agent');
    }

    public function testGetIntReturnsIntValue(): void
    {
        $_SERVER['HTTP_CONTENT_LENGTH'] = '1024';

        self::assertSame(1024, (new EnvironmentHeaderContext())->getInt('Content-Length'));
    }

    public function testGetIntThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getInt('Content-Length');
    }

    public function testGetBoolReturnsBoolValue(): void
    {
        $_SERVER['HTTP_X_FLAG'] = '1';

        self::assertTrue((new EnvironmentHeaderContext())->getBool('X-Flag'));
    }

    public function testGetBoolReturnsFalseForEmptyValue(): void
    {
        $_SERVER['HTTP_X_FLAG'] = '';

        self::assertFalse((new EnvironmentHeaderContext())->getBool('X-Flag'));
    }

    public function testGetBoolThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getBool('X-Flag');
    }

    public function testGetFloatReturnsFloatValue(): void
    {
        $_SERVER['HTTP_X_RATIO'] = '0.75';

        self::assertSame(0.75, (new EnvironmentHeaderContext())->getFloat('X-Ratio'));
    }

    public function testGetFloatThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getFloat('X-Ratio');
    }

    public function testGetStringReturnsStringValue(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla';

        self::assertSame('Mozilla', (new EnvironmentHeaderContext())->getString('User-Agent'));
    }

    public function testGetStringThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getString('User-Agent');
    }

    public function testGetEnumReturnsMatchingCase(): void
    {
        $_SERVER['HTTP_X_CHOICE'] = 'foo';

        self::assertSame(
            InputContextEnum::FOO,
            (new EnvironmentHeaderContext())->getEnum('X-Choice', InputContextEnum::class),
        );
    }

    public function testGetEnumMatchesCaseInsensitively(): void
    {
        $_SERVER['HTTP_X_CHOICE'] = 'BAR';

        self::assertSame(
            InputContextEnum::BAR,
            (new EnvironmentHeaderContext())->getEnum('X-Choice', InputContextEnum::class),
        );
    }

    public function testGetEnumThrowsWhenEnumClassDoesNotExist(): void
    {
        $_SERVER['HTTP_X_CHOICE'] = 'foo';

        $this->expectException(HttpException::class);

        /** @phpstan-ignore-next-line argument.type */
        (new EnvironmentHeaderContext())->getEnum('X-Choice', 'NonExistentEnum');
    }

    public function testGetEnumThrowsWhenHeaderMissing(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getEnum('X-Choice', InputContextEnum::class);
    }

    public function testGetEnumThrowsWhenValueDoesNotMatchAnyCase(): void
    {
        $_SERVER['HTTP_X_CHOICE'] = 'unknown';

        $this->expectException(HttpException::class);

        (new EnvironmentHeaderContext())->getEnum('X-Choice', InputContextEnum::class);
    }

    public function testHeaderNameIsNormalizedFromServerKey(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'value';

        $context = new EnvironmentHeaderContext();

        self::assertTrue($context->has('X-Custom-Header'));
        self::assertFalse($context->has('x-custom-header'));
    }
}
