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

namespace Unit\Http\Client\Body;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\FormBody;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\SameSite;

class FormBodyTest extends TestCase
{
    /**
     * @param array<mixed>|object $fields
     */
    private static function encode(
        array|object $fields,
        bool $bracketArrays = false,
    ): string {
        return new FormBody($fields, $bracketArrays)->encode(SerializerManagerFactory::create());
    }

    public function testEncodesScalars(): void
    {
        self::assertSame(
            'name=John+Doe&age=30&ratio=0.5&active=1&deleted=0&q=a%26b%3Dc',
            self::encode(
                [
                    'name' => 'John Doe',
                    'age' => 30,
                    'ratio' => 0.5,
                    'active' => true,
                    'deleted' => false,
                    'skipped' => null,
                    'q' => 'a&b=c',
                ],
            ),
        );
    }

    public function testListsRepeatTheNameByDefault(): void
    {
        self::assertSame(
            'tag=a&tag=b',
            self::encode(
                [
                    'tag' => [
                        'a',
                        'b',
                    ],
                ],
            ),
        );
    }

    public function testListsUseBracketsWhenAsked(): void
    {
        self::assertSame(
            'tag%5B%5D=a&tag%5B%5D=b',
            self::encode(
                [
                    'tag' => [
                        'a',
                        'b',
                    ],
                ],
                bracketArrays: true,
            ),
        );
    }

    public function testNestedArraysUseBrackets(): void
    {
        self::assertSame(
            'user%5Bname%5D=Ada&rows%5B0%5D%5Bid%5D=1',
            self::encode(
                [
                    'user' => [
                        'name' => 'Ada',
                    ],
                    'rows' => [
                        [
                            'id' => 1,
                        ],
                    ],
                ],
            ),
        );
    }

    public function testEncodesEnumsStringablesAndOtherObjects(): void
    {
        $stringable = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'text';
            }
        };

        self::assertSame(
            'same_site=Lax&text=text&method=GET&other=',
            self::encode(
                [
                    'same_site' => SameSite::LAX,
                    'text' => $stringable,
                    'method' => Method::GET,
                    'other' => new \stdClass(),
                ],
            ),
        );
    }

    public function testEncodesObjectsThroughTheArraySerializer(): void
    {
        self::assertSame('name=John&age=30', self::encode(new BodyContextFixture('John', 30)));
    }

    public function testContentType(): void
    {
        self::assertSame('application/x-www-form-urlencoded', new FormBody([])->contentType);
    }
}
