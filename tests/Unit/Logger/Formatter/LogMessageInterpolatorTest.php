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

namespace Unit\Logger\Formatter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\Formatter\LogMessageInterpolator;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Temporal\Instant;

class LogMessageInterpolatorTest extends TestCase
{
    /**
     * @return \Generator<array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function interpolateDataProvider(): \Generator
    {
        yield [
            'Hello World',
            [],
            'Hello World',
        ];

        yield [
            'Hello {name}',
            [
                'name' => 'Kalle',
            ],
            'Hello Kalle',
        ];

        yield [
            '{greeting} {name}, you are {age}',
            [
                'greeting' => 'Hello',
                'name' => 'Kalle',
                'age' => 36,
            ],
            'Hello Kalle, you are 36',
        ];

        yield [
            'No placeholders here',
            [
                'unused' => 'value',
            ],
            'No placeholders here',
        ];

        yield [
            'Value is {val}',
            [
                'val' => 3.14,
            ],
            'Value is 3.14',
        ];

        yield [
            'Ratio is {ratio}',
            [
                'ratio' => \NAN,
            ],
            'Ratio is NAN',
        ];

        yield [
            'Limit is {limit}',
            [
                'limit' => \INF,
            ],
            'Limit is INF',
        ];

        yield [
            'Count is {count}',
            [
                'count' => 0,
            ],
            'Count is 0',
        ];

        yield [
            'Flag is {flag}',
            [
                'flag' => true,
            ],
            'Flag is true',
        ];

        yield [
            'Flag is {flag}',
            [
                'flag' => false,
            ],
            'Flag is false',
        ];

        yield [
            'User is {user}',
            [
                'user' => null,
            ],
            'User is null',
        ];

        yield [
            'Level is {level}',
            [
                'level' => LogLevel::WARNING,
            ],
            'Level is 4',
        ];

        yield [
            'Format is {format}',
            [
                'format' => LogFormat::JSON,
            ],
            'Format is JSON',
        ];

        yield [
            'Ids are {ids}',
            [
                'ids' => [
                    1,
                    2,
                ],
            ],
            'Ids are {ids}',
        ];

        yield [
            'Object is {object}',
            [
                'object' => new \stdClass(),
            ],
            'Object is {object}',
        ];

        yield [
            'Stringable is {value}',
            [
                'value' => new class () implements \Stringable {
                    public function __toString(): string
                    {
                        return 'string';
                    }
                },
            ],
            'Stringable is {value}',
        ];

        yield [
            'Missing is {missing}',
            [],
            'Missing is {missing}',
        ];

        yield [
            'path={path} id={id}',
            [
                'path' => '/u/{id}',
                'id' => 7,
            ],
            'path=/u/{id} id=7',
        ];

        yield [
            'Literal \\{name\\}',
            [
                'name' => 'Kalle',
            ],
            'Literal {name}',
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('interpolateDataProvider')]
    public function testInterpolate(
        string $message,
        array $context,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            (new LogMessageInterpolator())->interpolate(
                new LogRecord(
                    message: $message,
                    level: LogLevel::INFO,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                    context: $context,
                ),
            ),
        );
    }
}
