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

use Fixture\Http\Request\Context\BodyContextFixture;
use Fixture\Http\Request\Context\InputContextEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\Context\EnvironmentInputContext;

class EnvironmentInputContextTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
    }

    private function makeContext(
        InputContext $inputContext = InputContext::GET,
    ): EnvironmentInputContext {
        return new EnvironmentInputContext(
            inputContext: $inputContext,
        );
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $_GET['name'] = 'value';

        self::assertTrue($this->makeContext()->has('name'));
    }

    public function testHasReturnsFalseWhenKeyDoesNotExist(): void
    {
        self::assertFalse($this->makeContext()->has('missing'));
    }

    public function testInputSourceUsesPostSuperglobal(): void
    {
        $_POST['name'] = 'post-value';

        self::assertTrue(
            $this->makeContext(
                inputContext: InputContext::POST,
            )->has('name'),
        );
    }

    public function testInputSourceUsesCookieSuperglobal(): void
    {
        $_COOKIE['name'] = 'cookie-value';

        self::assertTrue(
            $this->makeContext(
                inputContext: InputContext::COOKIE,
            )->has('name'),
        );
    }

    public function testRawReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', $this->makeContext()->raw('missing', 'fallback'));
    }

    public function testRawReturnsFilteredScalar(): void
    {
        $_GET['name'] = 'hello';

        self::assertSame('hello', $this->makeContext()->raw('name'));
    }

    public function testRawReturnsDefaultWhenValueIsArray(): void
    {
        $_GET['name'] = [
            'a',
            'b',
        ];

        self::assertSame('fallback', $this->makeContext()->raw('name', 'fallback'));
    }

    public function testRawReturnsDefaultWhenDefaultIsFalse(): void
    {
        $_GET['name'] = 'hello';

        self::assertFalse($this->makeContext()->raw('name', false));
    }

    public function testRawArrayReturnsDefaultWhenMissing(): void
    {
        self::assertSame(
            [
                'fallback',
            ],
            $this->makeContext()->rawArray(
                'missing',
                [
                    'fallback',
                ],
            ),
        );
    }

    public function testRawArrayReturnsFilteredArray(): void
    {
        $_GET['names'] = [
            'a',
            'b',
        ];

        self::assertSame(
            [
                'a',
                'b',
            ],
            $this->makeContext()->rawArray('names'),
        );
    }

    public function testRawArrayReturnsDefaultWhenValueIsNotArray(): void
    {
        $_GET['name'] = 'hello';

        self::assertSame(
            [
                'fallback',
            ],
            $this->makeContext()->rawArray(
                'name',
                [
                    'fallback',
                ],
            ),
        );
    }

    public function testIntReturnsDefaultWhenMissing(): void
    {
        self::assertSame(7, $this->makeContext()->int('missing', 7));
    }

    public function testIntReturnsParsedInt(): void
    {
        $_GET['age'] = '42';

        self::assertSame(42, $this->makeContext()->int('age'));
    }

    public function testIntReturnsDefaultForNonIntString(): void
    {
        $_GET['age'] = 'abc';

        self::assertSame(7, $this->makeContext()->int('age', 7));
    }

    public function testBoolReturnsDefaultWhenMissing(): void
    {
        self::assertTrue($this->makeContext()->bool('missing', true));
    }

    public function testBoolReturnsTrueForTruthyString(): void
    {
        $_GET['flag'] = 'true';

        self::assertTrue($this->makeContext()->bool('flag'));
    }

    public function testBoolReturnsFalseForFalsyString(): void
    {
        $_GET['flag'] = 'false';

        self::assertFalse($this->makeContext()->bool('flag'));
    }

    public function testBoolReturnsDefaultForNonBoolString(): void
    {
        $_GET['flag'] = 'not-a-bool';

        self::assertTrue($this->makeContext()->bool('flag', true));
    }

    public function testFloatReturnsDefaultWhenMissing(): void
    {
        self::assertSame(1.5, $this->makeContext()->float('missing', 1.5));
    }

    public function testFloatReturnsParsedFloat(): void
    {
        $_GET['amount'] = '3.14';

        self::assertSame(3.14, $this->makeContext()->float('amount'));
    }

    public function testFloatRespectsDecimalAndThousandSeparator(): void
    {
        $_GET['amount'] = '1.234,56';

        self::assertSame(
            1234.56,
            $this->makeContext()->float(
                name: 'amount',
                decimalPoint: ',',
                thousandSeparator: '.',
            ),
        );
    }

    public function testFloatReturnsDefaultForNonFloatString(): void
    {
        $_GET['amount'] = 'abc';

        self::assertSame(1.5, $this->makeContext()->float('amount', 1.5));
    }

    public function testStringReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', $this->makeContext()->string('missing', 'fallback'));
    }

    public function testStringReturnsFilteredString(): void
    {
        $_GET['name'] = 'hello';

        self::assertSame('hello', $this->makeContext()->string('name'));
    }

    public function testStringReturnsDefaultWhenValueIsArray(): void
    {
        $_GET['name'] = [
            'a',
        ];

        self::assertSame('fallback', $this->makeContext()->string('name', 'fallback'));
    }

    public function testEnumReturnsMatchingCase(): void
    {
        $_GET['choice'] = 'foo';

        self::assertSame(
            InputContextEnum::FOO,
            $this->makeContext()->enum('choice', InputContextEnum::class),
        );
    }

    public function testEnumThrowsWhenKeyMissing(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->enum('missing', InputContextEnum::class);
    }

    public function testEnumThrowsWhenValueIsArray(): void
    {
        $_GET['choice'] = [
            'foo',
        ];

        $this->expectException(HttpException::class);

        $this->makeContext()->enum('choice', InputContextEnum::class);
    }

    public function testEnumThrowsWhenValueDoesNotMatchAnyCase(): void
    {
        $_GET['choice'] = 'unknown';

        $this->expectException(HttpException::class);

        $this->makeContext()->enum('choice', InputContextEnum::class);
    }

    public function testArrayOfIntReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->arrayOfInt('missing'));
    }

    public function testArrayOfIntReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['ids'] = '1';

        self::assertSame([], $this->makeContext()->arrayOfInt('ids'));
    }

    public function testArrayOfIntFiltersOutNonIntValues(): void
    {
        $_GET['ids'] = [
            '1',
            'abc',
            '3',
        ];

        self::assertSame(
            [
                0 => 1,
                2 => 3,
            ],
            $this->makeContext()->arrayOfInt('ids'),
        );
    }

    public function testArrayOfBoolReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->arrayOfBool('missing'));
    }

    public function testArrayOfBoolReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['flags'] = 'true';

        self::assertSame([], $this->makeContext()->arrayOfBool('flags'));
    }

    public function testArrayOfBoolFiltersOutNonBoolValues(): void
    {
        $_GET['flags'] = [
            'true',
            'maybe',
            'false',
        ];

        self::assertSame(
            [
                0 => true,
                2 => false,
            ],
            $this->makeContext()->arrayOfBool('flags'),
        );
    }

    public function testArrayOfFloatReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->arrayOfFloat('missing'));
    }

    public function testArrayOfFloatReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['amounts'] = '1.5';

        self::assertSame([], $this->makeContext()->arrayOfFloat('amounts'));
    }

    public function testArrayOfFloatFiltersOutNonFloatValues(): void
    {
        $_GET['amounts'] = [
            '1.5',
            'abc',
            '2.5',
        ];

        self::assertSame(
            [
                0 => 1.5,
                2 => 2.5,
            ],
            $this->makeContext()->arrayOfFloat('amounts'),
        );
    }

    public function testArrayOfFloatRespectsDecimalAndThousandSeparator(): void
    {
        $_GET['amounts'] = [
            '1.234,56',
            '7,5',
        ];

        self::assertSame(
            [
                0 => 1234.56,
                1 => 7.5,
            ],
            $this->makeContext()->arrayOfFloat(
                name: 'amounts',
                decimalPoint: ',',
                thousandSeparator: '.',
            ),
        );
    }

    public function testArrayOfStringReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->arrayOfString('missing'));
    }

    public function testArrayOfStringReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['names'] = 'foo';

        self::assertSame([], $this->makeContext()->arrayOfString('names'));
    }

    public function testArrayOfStringFiltersOutNonStringValues(): void
    {
        $_GET['names'] = [
            'foo',
            [
                'nested',
            ],
            'bar',
        ];

        self::assertSame(
            [
                0 => 'foo',
                2 => 'bar',
            ],
            $this->makeContext()->arrayOfString('names'),
        );
    }

    public function testArrayOfEnumReturnsMatchingCases(): void
    {
        $_GET['choices'] = [
            'foo',
            'BAR',
        ];

        self::assertSame(
            [
                InputContextEnum::FOO,
                InputContextEnum::BAR,
            ],
            $this->makeContext()->arrayOfEnum('choices', InputContextEnum::class),
        );
    }

    public function testArrayOfEnumThrowsWhenKeyMissing(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->arrayOfEnum('missing', InputContextEnum::class);
    }

    public function testArrayOfEnumThrowsWhenValueIsNotArray(): void
    {
        $_GET['choices'] = 'foo';

        $this->expectException(HttpException::class);

        $this->makeContext()->arrayOfEnum('choices', InputContextEnum::class);
    }

    public function testArrayOfEnumThrowsWhenValueDoesNotMatchAnyCase(): void
    {
        $_GET['choices'] = [
            'foo',
            'unknown',
        ];

        $this->expectException(HttpException::class);

        $this->makeContext()->arrayOfEnum('choices', InputContextEnum::class);
    }

    public function testArrayOfEnumSkipsNonStringValues(): void
    {
        $_GET['choices'] = [
            'foo',
            [
                'nested',
            ],
            'bar',
        ];

        self::assertSame(
            [
                InputContextEnum::FOO,
                InputContextEnum::BAR,
            ],
            $this->makeContext()->arrayOfEnum('choices', InputContextEnum::class),
        );
    }

    public function testMapToMapsArrayValueToObject(): void
    {
        $_POST['user'] = [
            'name' => 'John',
            'age' => 30,
        ];

        $result = $this->makeContext(
            inputContext: InputContext::POST,
        )->mapTo('user', BodyContextFixture::class);

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testMapToThrowsForCookieContext(): void
    {
        $_COOKIE['user'] = [
            'name' => 'John',
        ];

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::COOKIE,
        )->mapTo('user', BodyContextFixture::class);
    }

    public function testMapToArrayOfMapsArrayOfArraysToObjects(): void
    {
        $_POST['users'] = [
            [
                'name' => 'John',
                'age' => 30,
            ],
            [
                'name' => 'Jane',
                'age' => 25,
            ],
        ];

        $result = $this->makeContext(
            inputContext: InputContext::POST,
        )->mapToArrayOf('users', BodyContextFixture::class);

        self::assertCount(2, $result);
        self::assertInstanceOf(BodyContextFixture::class, $result[0]);
        self::assertSame('John', $result[0]->name);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testMapToArrayOfThrowsForCookieContext(): void
    {
        $_COOKIE['users'] = [
            [
                'name' => 'John',
            ],
        ];

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::COOKIE,
        )->mapToArrayOf('users', BodyContextFixture::class);
    }

    public function testJsonMapToMapsJsonObjectToObject(): void
    {
        $_POST['payload'] = '{"name":"John","age":30}';

        $result = $this->makeContext(
            inputContext: InputContext::POST,
        )->jsonMapTo('payload', BodyContextFixture::class);

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testJsonMapToThrowsForCookieContext(): void
    {
        $_COOKIE['payload'] = '{"name":"John"}';

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::COOKIE,
        )->jsonMapTo('payload', BodyContextFixture::class);
    }

    public function testJsonMapToThrowsWhenJsonDecodesToScalar(): void
    {
        $_POST['payload'] = '"just a string"';

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::POST,
        )->jsonMapTo('payload', BodyContextFixture::class);
    }

    public function testJsonMapToThrowsForInvalidJson(): void
    {
        $_POST['payload'] = '{not json';

        $this->expectException(\JsonException::class);

        $this->makeContext(
            inputContext: InputContext::POST,
        )->jsonMapTo('payload', BodyContextFixture::class);
    }

    public function testJsonMapToArrayOfMapsJsonArrayToObjects(): void
    {
        $_POST['payload'] = '[{"name":"John","age":30},{"name":"Jane","age":25}]';

        $result = $this->makeContext(
            inputContext: InputContext::POST,
        )->jsonMapToArrayOf('payload', BodyContextFixture::class);

        self::assertCount(2, $result);
        self::assertInstanceOf(BodyContextFixture::class, $result[0]);
        self::assertSame('John', $result[0]->name);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testJsonMapToArrayOfThrowsForCookieContext(): void
    {
        $_COOKIE['payload'] = '[]';

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::COOKIE,
        )->jsonMapToArrayOf('payload', BodyContextFixture::class);
    }

    public function testJsonMapToArrayOfThrowsWhenJsonDecodesToScalar(): void
    {
        $_POST['payload'] = '"just a string"';

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::POST,
        )->jsonMapToArrayOf('payload', BodyContextFixture::class);
    }
}
