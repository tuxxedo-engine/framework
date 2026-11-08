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

namespace Tuxxedo\Serializer;

class SerializerInputException extends SerializerException
{
    public static function fromMalformedInput(
        string $format,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                'Malformed %s input: %s',
                $format,
                $previous->getMessage(),
            ),
            previous: $previous,
        );
    }

    public static function fromExpectedObject(
        string $format,
    ): self {
        return new self(
            message: \sprintf(
                'The %s root must be an object',
                $format,
            ),
        );
    }

    public static function fromExpectedList(
        string $format,
    ): self {
        return new self(
            message: \sprintf(
                'The %s root must be a list',
                $format,
            ),
        );
    }

    public static function fromCsvColumnCount(
        int $row,
        int $expected,
        int $actual,
    ): self {
        return new self(
            message: \sprintf(
                'Malformed CSV input: row %d has %d fields, the header has %d',
                $row,
                $actual,
                $expected,
            ),
        );
    }

    public static function fromMalformedXml(
        string $reason,
    ): self {
        return new self(
            message: \sprintf(
                'Malformed XML input: %s',
                $reason,
            ),
        );
    }

    public static function fromEmptyXml(): self
    {
        return new self(
            message: 'XML input is empty',
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromXmlWithoutRoot(): self
    {
        return new self(
            message: 'XML input has no root element',
        );
    }

    public static function fromXmlDoctype(): self
    {
        return new self(
            message: 'XML input must not contain a DOCTYPE',
        );
    }

    public static function fromNonArrayKey(
        string $format,
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'The %s value at key "%s" must be an object or list',
                $format,
                $key,
            ),
        );
    }
}
