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

namespace Support\Console\Stream;

use Tuxxedo\Console\Stream\InputStreamInterface;

class ScriptedInputStream implements InputStreamInterface
{
    public private(set) bool $closed = false;

    /**
     * @var list<string>
     */
    private array $lines;

    /**
     * @param list<string> $lines
     */
    public function __construct(
        array $lines = [],
        public readonly bool $isTerminal = false,
    ) {
        $this->lines = $lines;
    }

    public function read(
        int $length,
    ): string {
        if ($this->lines === []) {
            return '';
        }

        $head = \array_shift($this->lines);
        $chunk = \substr($head, 0, $length);
        $rest = \substr($head, $length);

        if ($rest !== '') {
            \array_unshift($this->lines, $rest);
        }

        return $chunk;
    }

    public function readLine(): ?string
    {
        if ($this->lines === []) {
            return null;
        }

        return \array_shift($this->lines);
    }

    public function readAll(): string
    {
        $remaining = \join("\n", $this->lines);
        $this->lines = [];

        return $remaining;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
