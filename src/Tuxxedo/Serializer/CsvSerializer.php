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

use Tuxxedo\Serializer\MetaData\ClassMetaData;
use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Validator\ValidatorInterface;

class CsvSerializer extends AbstractSerializer implements CsvSerializerInterface
{
    private const string BOM = "\xEF\xBB\xBF";

    /**
     * @throws SerializerException
     */
    public function __construct(
        ?ValidatorInterface $validator = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        public readonly string $separator = ',',
        public readonly string $enclosure = '"',
        ?MetaDataReaderInterface $metaData = null,
    ) {
        if (\strlen($separator) !== 1) {
            throw SerializerException::fromCsvCharacter(
                option: 'separator',
                value: $separator,
            );
        }

        if (\strlen($enclosure) !== 1) {
            throw SerializerException::fromCsvCharacter(
                option: 'enclosure',
                value: $enclosure,
            );
        }

        parent::__construct(
            validator: $validator,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            metaData: $metaData,
        );
    }

    public function decode(
        string $csv,
        string $className,
    ): array {
        if (\str_starts_with($csv, self::BOM)) {
            $csv = \substr($csv, \strlen(self::BOM));
        }

        $handle = $this->buffer();

        try {
            \fwrite($handle, $csv);
            \rewind($handle);

            $data = \iterator_to_array(
                iterator: $this->rows($handle),
                preserve_keys: false,
            );
        } finally {
            \fclose($handle);
        }

        return $this->hydrateList($data, $className);
    }

    public function decodeStream(
        mixed $stream,
        string $className,
    ): \Generator {
        foreach ($this->rows($stream) as $index => $row) {
            yield $index => $this->hydrateObject(
                data: $row,
                className: $className,
                path: (string) $index,
            );
        }
    }

    public function encode(
        iterable $values,
    ): string {
        $rows = [];
        $columns = [];

        foreach ($values as $value) {
            $row = $this->flatten(
                data: $this->extractObject($value),
                metaData: $this->metaData->read($value::class),
                prefix: '',
            );

            foreach (\array_keys($row) as $column) {
                $columns[$column] = true;
            }

            $rows[] = $row;
        }

        if (\sizeof($rows) === 0) {
            return '';
        }

        $header = self::header(\array_map(\strval(...), \array_keys($columns)));
        $handle = $this->buffer();

        try {
            $this->writeRow($handle, $header);

            foreach ($rows as $row) {
                $cells = [];

                foreach ($header as $column) {
                    $cells[] = self::cell($row[$column] ?? null);
                }

                $this->writeRow($handle, $cells);
            }

            \rewind($handle);

            return (string) \stream_get_contents($handle);
        } finally {
            \fclose($handle);
        }
    }

    protected function casting(): Casting
    {
        return Casting::LENIENT;
    }

    /**
     * @return resource
     *
     * @throws SerializerException
     */
    private function buffer(): mixed
    {
        $handle = \fopen('php://memory', 'r+');

        if ($handle === false) {
            throw SerializerException::fromCsvBuffer(); // @codeCoverageIgnore
        }

        return $handle;
    }

    /**
     * @param resource $handle
     * @return \Generator<int, array<mixed>>
     *
     * @throws SerializerInputException
     */
    private function rows(
        mixed $handle,
    ): \Generator {
        $header = null;
        $index = 0;

        while (($fields = $this->readRow($handle)) !== null) {
            if ($fields === [null]) {
                continue;
            }

            if ($header === null) {
                $header = $this->stripBom($fields);

                continue;
            }

            if (\sizeof($fields) !== \sizeof($header)) {
                throw SerializerInputException::fromCsvColumnCount(
                    row: $index,
                    expected: \sizeof($header),
                    actual: \sizeof($fields),
                );
            }

            yield $index++ => self::unflatten(
                header: $header,
                fields: $fields,
            );
        }
    }

    /**
     * @param list<string|null> $header
     * @return list<string|null>
     */
    private function stripBom(
        array $header,
    ): array {
        $first = $header[0] ?? null;

        if ($first === null || !\str_starts_with($first, self::BOM)) {
            return $header;
        }

        $first = \substr($first, \strlen(self::BOM));

        if (
            \strlen($first) >= 2 &&
            \str_starts_with($first, $this->enclosure) &&
            \str_ends_with($first, $this->enclosure)
        ) {
            $first = \str_replace(
                $this->enclosure . $this->enclosure,
                $this->enclosure,
                \substr($first, 1, -1),
            );
        }

        $header[0] = $first;

        return $header;
    }

    /**
     * @param resource $handle
     * @return list<string|null>|null
     */
    private function readRow(
        mixed $handle,
    ): ?array {
        $fields = \fgetcsv(
            stream: $handle,
            separator: $this->separator,
            enclosure: $this->enclosure,
            escape: '',
        );

        return $fields === false
            ? null
            : $fields;
    }

    /**
     * @param resource $handle
     * @param list<string> $cells
     */
    private function writeRow(
        mixed $handle,
        array $cells,
    ): void {
        \fputcsv(
            stream: $handle,
            fields: $cells,
            separator: $this->separator,
            enclosure: $this->enclosure,
            escape: '',
            eol: "\n",
        );
    }

    /**
     * @param array<mixed> $data
     * @return array<string, mixed>
     *
     * @throws SerializerException
     */
    private function flatten(
        array $data,
        ClassMetaData $metaData,
        string $prefix,
    ): array {
        $row = [];

        foreach ($metaData->properties as $property) {
            if (!\array_key_exists($property->key, $data)) {
                continue;
            }

            $value = $data[$property->key];
            $column = $prefix . $property->key;

            if (!\is_array($value)) {
                $row[$column] = $value;

                continue;
            }

            if (
                $property->typeName === null ||
                $property->builtin ||
                $property->converter !== null ||
                $property->listOf !== null ||
                $property->mapOf !== null ||
                !\class_exists($property->typeName)
            ) {
                throw SerializerException::fromCsvNestedValue(
                    className: $metaData->reflection->name,
                    property: $property->name,
                );
            }

            $row += $this->flatten(
                data: $value,
                metaData: $this->metaData->read($property->typeName),
                prefix: $column . '.',
            );
        }

        return $row;
    }

    /**
     * @param list<string|null> $header
     * @param list<string|null> $fields
     * @return array<mixed>
     */
    private static function unflatten(
        array $header,
        array $fields,
    ): array {
        $row = [];

        foreach ($header as $index => $column) {
            $value = $fields[$index];

            if ($value === null || $value === '') {
                continue;
            }

            $row = self::assign(
                target: $row,
                segments: \explode('.', (string) $column),
                value: $value,
            );
        }

        return $row;
    }

    /**
     * @param array<mixed> $target
     * @param non-empty-list<string> $segments
     * @return array<mixed>
     */
    private static function assign(
        array $target,
        array $segments,
        string $value,
    ): array {
        $segment = \array_shift($segments);

        if (\sizeof($segments) === 0) {
            $target[$segment] = $value;

            return $target;
        }

        $child = $target[$segment] ?? [];
        $target[$segment] = self::assign(
            target: \is_array($child)
                ? $child
                : [],
            segments: $segments,
            value: $value,
        );

        return $target;
    }

    /**
     * @param list<string> $columns
     * @return list<string>
     */
    private static function header(
        array $columns,
    ): array {
        $header = [];

        foreach ($columns as $column) {
            foreach ($columns as $other) {
                if (\str_starts_with($other, $column . '.')) {
                    continue 2;
                }
            }

            $header[] = $column;
        }

        return $header;
    }

    /**
     * @throws SerializerException
     */
    private static function cell(
        mixed $value,
    ): string {
        return match (true) {
            $value === null => '',
            $value === true => '1',
            $value === false => '0',
            \is_int($value), \is_float($value), \is_string($value) => (string) $value,
            default => throw SerializerException::fromInvalidValue('a scalar CSV cell'),
        };
    }
}
