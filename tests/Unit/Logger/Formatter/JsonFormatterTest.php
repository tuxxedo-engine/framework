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
use Tuxxedo\Logger\Formatter\JsonFormatter;
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\Formatter\UnencodableValueMode;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Serializer\JsonSerializer;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Temporal\Instant;

class JsonFormatterTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function decode(
        string $json,
    ): array {
        self::assertStringEndsWith("\n", $json);
        self::assertStringNotContainsString("\n", \substr($json, 0, -1));

        $data = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    private function createRecord(
        array $context = [],
        array $extra = [],
    ): LogRecord {
        return new LogRecord(
            message: 'message',
            level: LogLevel::INFO,
            timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            context: $context,
            extra: $extra,
        );
    }

    public function testDefaultsToMarker(): void
    {
        self::assertSame(UnencodableValueMode::MARKER, (new JsonFormatter())->unencodable);
    }

    public function testFormatsMinimalRecord(): void
    {
        self::assertSame(
            '{"timestamp":"2026-01-01T12:00:00.000000+00:00","level":"INFO","channel":null,"message":"Hello","message_template":"Hello","context":[],"extra":[],"exception":null}' . "\n",
            (new JsonFormatter())->format(
                new LogRecord(
                    message: 'Hello',
                    level: LogLevel::INFO,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                ),
            ),
        );
    }

    public function testFormatsTemplateContextAndExtra(): void
    {
        $data = $this->decode(
            (new JsonFormatter())->format(
                new LogRecord(
                    message: 'Order {orderId} paid',
                    level: LogLevel::NOTICE,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                    context: [
                        'orderId' => 42,
                        'amount' => 10.0,
                        'paid' => true,
                        'url' => 'https://tuxxedo.dev/orders/42',
                        'items' => [
                            'a',
                            'b',
                        ],
                    ],
                    channel: 'payments',
                    extra: [
                        'host.name' => 'localhost',
                    ],
                ),
            ),
        );

        self::assertSame('NOTICE', $data['level']);
        self::assertSame('payments', $data['channel']);
        self::assertSame('Order 42 paid', $data['message']);
        self::assertSame('Order {orderId} paid', $data['message_template']);
        self::assertSame(
            [
                'orderId' => 42,
                'amount' => 10.0,
                'paid' => true,
                'url' => 'https://tuxxedo.dev/orders/42',
                'items' => [
                    'a',
                    'b',
                ],
            ],
            $data['context'],
        );
        self::assertSame(['host.name' => 'localhost'], $data['extra']);
    }

    public function testDefaultFlagsKeepSlashesAndZeroFractions(): void
    {
        $json = (new JsonFormatter())->format(
            $this->createRecord(
                context: [
                    'url' => 'https://tuxxedo.dev/',
                    'amount' => 10.0,
                ],
            ),
        );

        self::assertStringContainsString('"url":"https://tuxxedo.dev/"', $json);
        self::assertStringContainsString('"amount":10.0', $json);
    }

    public function testUsesGivenSerializer(): void
    {
        $json = (new JsonFormatter(
            serializer: new JsonSerializer(
                skipValidation: true,
            ),
        ))->format(
            $this->createRecord(
                context: [
                    'url' => 'https://tuxxedo.dev/',
                ],
            ),
        );

        self::assertStringContainsString('"url":"https:\/\/tuxxedo.dev\/"', $json);
    }

    public function testEncodesEnumsAndInstantsThroughSerializer(): void
    {
        $at = Instant::parse(input: '2026-02-03T04:05:06.000000+00:00');
        $data = $this->decode(
            (new JsonFormatter())->format(
                $this->createRecord(
                    context: [
                        'level' => LogLevel::WARNING,
                        'format' => LogFormat::JSON,
                        'at' => $at,
                        'nested' => [
                            'level' => LogLevel::DEBUG,
                        ],
                    ],
                ),
            ),
        );

        self::assertIsArray($data['context']);
        self::assertSame(4, $data['context']['level']);
        self::assertSame('JSON', $data['context']['format']);
        self::assertSame($at->toIso8601(), $data['context']['at']);
        self::assertSame(['level' => 7], $data['context']['nested']);
    }

    public function testFormatsExceptionWithPrevious(): void
    {
        $previous = new \LogicException('inner', 1);
        $exception = new \RuntimeException('outer', 2, $previous);

        $data = $this->decode(
            (new JsonFormatter())->format(
                new LogRecord(
                    message: 'message',
                    level: LogLevel::ERROR,
                    timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
                    exception: $exception,
                ),
            ),
        );

        self::assertIsArray($data['exception']);
        self::assertSame(\RuntimeException::class, $data['exception']['class']);
        self::assertSame('outer', $data['exception']['message']);
        self::assertSame(2, $data['exception']['code']);
        self::assertSame(__FILE__, $data['exception']['file']);
        self::assertSame($exception->getLine(), $data['exception']['line']);
        self::assertSame($exception->getTraceAsString(), $data['exception']['trace']);
        self::assertIsArray($data['exception']['previous']);
        self::assertSame(\LogicException::class, $data['exception']['previous']['class']);
        self::assertSame('inner', $data['exception']['previous']['message']);
        self::assertNull($data['exception']['previous']['previous']);
    }

    public function testInvalidUtf8IsSubstituted(): void
    {
        $data = $this->decode(
            (new JsonFormatter())->format(
                $this->createRecord(
                    context: [
                        'bytes' => "a\xFFb",
                    ],
                ),
            ),
        );

        self::assertIsArray($data['context']);
        self::assertSame("a\u{FFFD}b", $data['context']['bytes']);
    }

    /**
     * @return \Generator<array{0: UnencodableValueMode, 1: array<string, mixed>, 2: array<string, mixed>}>
     */
    public static function unencodableDataProvider(): \Generator
    {
        yield [
            UnencodableValueMode::MARKER,
            [
                'orderId' => 42,
                'ratio' => '[unencodable:float]',
            ],
            [
                'host.name' => 'localhost',
                'load' => '[unencodable:float]',
            ],
        ];

        yield [
            UnencodableValueMode::OMIT,
            [
                'orderId' => 42,
            ],
            [
                'host.name' => 'localhost',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    #[DataProvider('unencodableDataProvider')]
    public function testUnencodableValues(
        UnencodableValueMode $mode,
        array $context,
        array $extra,
    ): void {
        $data = $this->decode(
            (new JsonFormatter(
                unencodable: $mode,
            ))->format(
                $this->createRecord(
                    context: [
                        'orderId' => 42,
                        'ratio' => \NAN,
                    ],
                    extra: [
                        'host.name' => 'localhost',
                        'load' => \INF,
                    ],
                ),
            ),
        );

        self::assertSame('message', $data['message']);
        self::assertSame($context, $data['context']);
        self::assertSame($extra, $data['extra']);
    }

    public function testUnencodableValueThrowsInStrictMode(): void
    {
        try {
            (new JsonFormatter(
                unencodable: UnencodableValueMode::STRICT,
            ))->format(
                $this->createRecord(
                    context: [
                        'orderId' => 42,
                        'ratio' => \NAN,
                    ],
                ),
            );

            self::fail('Expected LoggerException was not thrown');
        } catch (LoggerException $exception) {
            self::assertStringContainsString('"context.ratio"', $exception->getMessage());
            self::assertInstanceOf(SerializerException::class, $exception->getPrevious());
        }
    }
}
