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

class GeneratorStreamProxy implements StreamProxyInterface
{
    /**
     * @var \Generator<string>
     */
    private \Generator $inner;

    /**
     * @param \Closure(): \Generator<string>|\Generator<string> $generator
     */
    public function __construct(
        \Closure|\Generator $generator,
    ) {
        $this->inner = $generator instanceof \Closure
            ? $generator()
            : $generator;
    }

    public function eof(): bool
    {
        return !$this->inner->valid();
    }

    public function getSize(): null
    {
        return null;
    }

    public function read(): ?string
    {
        if (!$this->inner->valid()) {
            return null;
        }

        $buffer = $this->inner->current();

        $this->inner->next();

        return $buffer;
    }

    public function contents(): string
    {
        $buffer = '';

        while ($this->inner->valid()) {
            $buffer .= $this->inner->current();

            $this->inner->next();
        }

        return $buffer;
    }
}
