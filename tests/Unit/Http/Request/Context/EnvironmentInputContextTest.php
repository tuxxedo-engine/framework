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

    public function testGetRawReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', $this->makeContext()->getRaw('missing', 'fallback'));
    }

    public function testGetRawReturnsFilteredScalar(): void
    {
        $_GET['name'] = 'hello';

        self::assertSame('hello', $this->makeContext()->getRaw('name'));
    }

    public function testGetRawReturnsDefaultWhenValueIsArray(): void
    {
        $_GET['name'] = [
            'a',
            'b',
        ];

        self::assertSame('fallback', $this->makeContext()->getRaw('name', 'fallback'));
    }

    public function testGetRawReturnsDefaultWhenDefaultIsFalse(): void
    {
        $_GET['name'] = 'hello';

        self::assertFalse($this->makeContext()->getRaw('name', false));
    }

    public function testGetRawArrayReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', $this->makeContext()->getRawArray('missing', 'fallback'));
    }

    public function testGetRawArrayReturnsFilteredArray(): void
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
            $this->makeContext()->getRawArray('names'),
        );
    }

    public function testGetRawArrayReturnsDefaultWhenValueIsNotArray(): void
    {
        $_GET['name'] = 'hello';

        self::assertSame('fallback', $this->makeContext()->getRawArray('name', 'fallback'));
    }

    public function testGetRawArrayReturnsDefaultWhenDefaultIsFalse(): void
    {
        $_GET['names'] = [
            'a',
            'b',
        ];

        self::assertFalse($this->makeContext()->getRawArray('names', false));
    }

    public function testGetIntReturnsDefaultWhenMissing(): void
    {
        self::assertSame(7, $this->makeContext()->getInt('missing', 7));
    }

    public function testGetIntReturnsParsedInt(): void
    {
        $_GET['age'] = '42';

        self::assertSame(42, $this->makeContext()->getInt('age'));
    }

    public function testGetIntReturnsDefaultForNonIntString(): void
    {
        $_GET['age'] = 'abc';

        self::assertSame(7, $this->makeContext()->getInt('age', 7));
    }

    public function testGetBoolReturnsDefaultWhenMissing(): void
    {
        self::assertTrue($this->makeContext()->getBool('missing', true));
    }

    public function testGetBoolReturnsTrueForTruthyString(): void
    {
        $_GET['flag'] = 'true';

        self::assertTrue($this->makeContext()->getBool('flag'));
    }

    public function testGetBoolReturnsFalseForFalsyString(): void
    {
        $_GET['flag'] = 'false';

        self::assertFalse($this->makeContext()->getBool('flag'));
    }

    public function testGetBoolReturnsDefaultForNonBoolString(): void
    {
        $_GET['flag'] = 'not-a-bool';

        self::assertTrue($this->makeContext()->getBool('flag', true));
    }

    public function testGetFloatReturnsDefaultWhenMissing(): void
    {
        self::assertSame(1.5, $this->makeContext()->getFloat('missing', 1.5));
    }

    public function testGetFloatReturnsParsedFloat(): void
    {
        $_GET['amount'] = '3.14';

        self::assertSame(3.14, $this->makeContext()->getFloat('amount'));
    }

    public function testGetFloatRespectsDecimalAndThousandSeparator(): void
    {
        $_GET['amount'] = '1.234,56';

        self::assertSame(
            1234.56,
            $this->makeContext()->getFloat(
                name: 'amount',
                decimalPoint: ',',
                thousandSeparator: '.',
            ),
        );
    }

    public function testGetFloatReturnsDefaultForNonFloatString(): void
    {
        $_GET['amount'] = 'abc';

        self::assertSame(1.5, $this->makeContext()->getFloat('amount', 1.5));
    }

    public function testGetStringReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', $this->makeContext()->getString('missing', 'fallback'));
    }

    public function testGetStringReturnsFilteredString(): void
    {
        $_GET['name'] = 'hello';

        self::assertSame('hello', $this->makeContext()->getString('name'));
    }

    public function testGetStringReturnsDefaultWhenValueIsArray(): void
    {
        $_GET['name'] = [
            'a',
        ];

        self::assertSame('fallback', $this->makeContext()->getString('name', 'fallback'));
    }

    public function testGetEnumReturnsMatchingCase(): void
    {
        $_GET['choice'] = 'foo';

        self::assertSame(
            InputContextEnum::FOO,
            $this->makeContext()->getEnum('choice', InputContextEnum::class),
        );
    }

    public function testGetEnumThrowsWhenEnumClassDoesNotExist(): void
    {
        $_GET['choice'] = 'foo';

        $this->expectException(HttpException::class);

        /** @phpstan-ignore-next-line argument.type */
        $this->makeContext()->getEnum('choice', 'NonExistentEnum');
    }

    public function testGetEnumThrowsWhenKeyMissing(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->getEnum('missing', InputContextEnum::class);
    }

    public function testGetEnumThrowsWhenValueIsArray(): void
    {
        $_GET['choice'] = [
            'foo',
        ];

        $this->expectException(HttpException::class);

        $this->makeContext()->getEnum('choice', InputContextEnum::class);
    }

    public function testGetEnumThrowsWhenValueDoesNotMatchAnyCase(): void
    {
        $_GET['choice'] = 'unknown';

        $this->expectException(HttpException::class);

        $this->makeContext()->getEnum('choice', InputContextEnum::class);
    }

    public function testGetArrayOfIntReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->getArrayOfInt('missing'));
    }

    public function testGetArrayOfIntReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['ids'] = '1';

        self::assertSame([], $this->makeContext()->getArrayOfInt('ids'));
    }

    public function testGetArrayOfIntFiltersOutNonIntValues(): void
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
            $this->makeContext()->getArrayOfInt('ids'),
        );
    }

    public function testGetArrayOfBoolReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->getArrayOfBool('missing'));
    }

    public function testGetArrayOfBoolReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['flags'] = 'true';

        self::assertSame([], $this->makeContext()->getArrayOfBool('flags'));
    }

    public function testGetArrayOfBoolFiltersOutNonBoolValues(): void
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
            $this->makeContext()->getArrayOfBool('flags'),
        );
    }

    public function testGetArrayOfFloatReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->getArrayOfFloat('missing'));
    }

    public function testGetArrayOfFloatReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['amounts'] = '1.5';

        self::assertSame([], $this->makeContext()->getArrayOfFloat('amounts'));
    }

    public function testGetArrayOfFloatFiltersOutNonFloatValues(): void
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
            $this->makeContext()->getArrayOfFloat('amounts'),
        );
    }

    public function testGetArrayOfFloatRespectsDecimalAndThousandSeparator(): void
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
            $this->makeContext()->getArrayOfFloat(
                name: 'amounts',
                decimalPoint: ',',
                thousandSeparator: '.',
            ),
        );
    }

    public function testGetArrayOfStringReturnsEmptyArrayWhenMissing(): void
    {
        self::assertSame([], $this->makeContext()->getArrayOfString('missing'));
    }

    public function testGetArrayOfStringReturnsEmptyArrayWhenValueIsScalar(): void
    {
        $_GET['names'] = 'foo';

        self::assertSame([], $this->makeContext()->getArrayOfString('names'));
    }

    public function testGetArrayOfStringFiltersOutNonStringValues(): void
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
            $this->makeContext()->getArrayOfString('names'),
        );
    }

    public function testGetArrayOfEnumReturnsMatchingCases(): void
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
            $this->makeContext()->getArrayOfEnum('choices', InputContextEnum::class),
        );
    }

    public function testGetArrayOfEnumThrowsWhenEnumClassDoesNotExist(): void
    {
        $_GET['choices'] = [
            'foo',
        ];

        $this->expectException(HttpException::class);

        /** @phpstan-ignore-next-line argument.type */
        $this->makeContext()->getArrayOfEnum('choices', 'NonExistentEnum');
    }

    public function testGetArrayOfEnumThrowsWhenKeyMissing(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext()->getArrayOfEnum('missing', InputContextEnum::class);
    }

    public function testGetArrayOfEnumThrowsWhenValueIsNotArray(): void
    {
        $_GET['choices'] = 'foo';

        $this->expectException(HttpException::class);

        $this->makeContext()->getArrayOfEnum('choices', InputContextEnum::class);
    }

    public function testGetArrayOfEnumThrowsWhenValueDoesNotMatchAnyCase(): void
    {
        $_GET['choices'] = [
            'foo',
            'unknown',
        ];

        $this->expectException(HttpException::class);

        $this->makeContext()->getArrayOfEnum('choices', InputContextEnum::class);
    }

    public function testGetArrayOfEnumSkipsNonStringValues(): void
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
            $this->makeContext()->getArrayOfEnum('choices', InputContextEnum::class),
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

    public function testMapToThrowsWhenValueIsNotArray(): void
    {
        $_POST['user'] = 'not-an-array';

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::POST,
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

    public function testMapToArrayOfThrowsWhenValueIsNotArray(): void
    {
        $_POST['users'] = 'not-an-array';

        $this->expectException(HttpException::class);

        $this->makeContext(
            inputContext: InputContext::POST,
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
