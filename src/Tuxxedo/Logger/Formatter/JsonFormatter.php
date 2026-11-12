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

namespace Tuxxedo\Logger\Formatter;

use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Serializer\JsonSerializer;
use Tuxxedo\Serializer\JsonSerializerInterface;
use Tuxxedo\Serializer\SerializerException;

class JsonFormatter implements LogFormatterInterface
{
    private const int FLAGS = \JSON_UNESCAPED_SLASHES |
        \JSON_UNESCAPED_UNICODE |
        \JSON_PRESERVE_ZERO_FRACTION |
        \JSON_INVALID_UTF8_SUBSTITUTE;

    private readonly JsonSerializerInterface $serializer;
    private readonly LogMessageInterpolatorInterface $interpolator;

    /**
     * @throws SerializerException
     */
    public function __construct(
        ?JsonSerializerInterface $serializer = null,
        public readonly UnencodableValueMode $unencodable = UnencodableValueMode::MARKER,
        ?LogMessageInterpolatorInterface $interpolator = null,
    ) {
        $this->serializer = $serializer ?? new JsonSerializer(
            skipValidation: true,
            flags: self::FLAGS,
        );

        $this->interpolator = $interpolator ?? new LogMessageInterpolator();
    }

    /**
     * @throws LoggerException
     * @throws SerializerException
     */
    public function format(
        LogRecordInterface $record,
    ): string {
        $data = [
            'timestamp' => $record->timestamp->format('Y-m-d\TH:i:s.uP'),
            'level' => $record->level->name,
            'channel' => $record->channel,
            'message' => $this->interpolator->interpolate($record),
            'message_template' => $record->message,
            'context' => $record->context,
            'extra' => $record->extra,
            'exception' => $record->exception !== null
                ? self::normalizeException($record->exception)
                : null,
        ];

        try {
            return $this->serializer->encodeValue($data) . "\n";
        } catch (SerializerException) {
            $data['context'] = $this->clean(
                section: 'context',
                values: $record->context,
            );

            $data['extra'] = $this->clean(
                section: 'extra',
                values: $record->extra,
            );

            return $this->serializer->encodeValue($data) . "\n";
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     *
     * @throws LoggerException
     */
    private function clean(
        string $section,
        array $values,
    ): array {
        $cleaned = [];

        foreach ($values as $key => $value) {
            try {
                $this->serializer->encodeValue(
                    [
                        $section => [
                            $key => $value,
                        ],
                    ],
                );

                $cleaned[$key] = $value;
            } catch (SerializerException $exception) {
                if ($this->unencodable === UnencodableValueMode::STRICT) {
                    throw LoggerException::fromUnencodableValue(
                        key: $section . '.' . $key,
                        previous: $exception,
                    );
                }

                if ($this->unencodable === UnencodableValueMode::MARKER) {
                    $cleaned[$key] = '[unencodable:' . \get_debug_type($value) . ']';
                }
            }
        }

        return $cleaned;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeException(
        \Throwable $exception,
    ): array {
        $previous = $exception->getPrevious();

        return [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $previous !== null
                ? self::normalizeException($previous)
                : null,
        ];
    }
}
