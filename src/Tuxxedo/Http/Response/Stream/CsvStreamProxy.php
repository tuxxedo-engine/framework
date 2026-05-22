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

namespace Tuxxedo\Http\Response\Stream;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\Response\PrefersHeadersInterface;

class CsvStreamProxy implements StreamProxyInterface, PrefersHeadersInterface
{
    /**
     * @var \Generator<scalar[]>
     */
    private \Generator $inner;
    private bool $done = false;
    private bool $columnsSent = false;

    public readonly array $headers;

    /**
     * @param \Closure(): \Generator<scalar[]>|\Generator<scalar[]> $generator
     * @param string[]|null $columns
     */
    public function __construct(
        \Closure|\Generator $generator,
        private readonly string $separator = ',',
        private readonly string $enclosure = '"',
        private readonly string $eol = "\n",
        private readonly ?array $columns = null,
    ) {
        $this->inner = $generator instanceof \Closure
            ? $generator()
            : $generator;

        $this->headers = [
            new Header('Content-Type', 'text/csv; charset=utf-8'),
        ];
    }

    public function eof(): bool
    {
        return $this->done;
    }

    public function getSize(): null
    {
        return null;
    }

    public function read(): ?string
    {
        if ($this->done) {
            return null;
        }

        if (!$this->columnsSent && $this->columns !== null) {
            $this->columnsSent = true;

            return $this->formatRow($this->columns);
        }

        if (!$this->inner->valid()) {
            $this->done = true;

            return null;
        }

        $row = $this->inner->current();

        $this->inner->next();

        if (!$this->inner->valid()) {
            $this->done = true;
        }

        return $this->formatRow($row);
    }

    public function contents(): string
    {
        $buffer = '';

        while (!$this->eof()) {
            $chunk = $this->read();

            if ($chunk === null) {
                break;
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    /**
     * @param array<scalar> $row
     */
    private function formatRow(
        array $row,
    ): string {
        $fields = [];

        foreach ($row as $field) {
            $field = (string) $field;

            if (
                \str_contains($field, $this->separator) ||
                \str_contains($field, $this->enclosure) ||
                \str_contains($field, "\n") ||
                \str_contains($field, "\r")
            ) {
                $field = $this->enclosure .
                    \str_replace($this->enclosure, $this->enclosure . $this->enclosure, $field) .
                    $this->enclosure;
            }

            $fields[] = $field;
        }

        return \join($this->separator, $fields) . $this->eol;
    }
}
