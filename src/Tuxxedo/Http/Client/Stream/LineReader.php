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

namespace Tuxxedo\Http\Client\Stream;

use Tuxxedo\Http\Stream\StreamInterface;

class LineReader
{
    /**
     * @return \Generator<int, string>
     */
    public static function read(
        StreamInterface $stream,
    ): \Generator {
        $buffer = '';

        while (($chunk = $stream->read()) !== null) {
            $buffer .= $chunk;
            $held = \str_ends_with($buffer, "\r")
                ? "\r"
                : '';

            $lines = \explode(
                "\n",
                \str_replace(
                    [
                        "\r\n",
                        "\r",
                    ],
                    "\n",
                    $held !== ''
                        ? \substr($buffer, 0, -1)
                        : $buffer,
                ),
            );

            $buffer = \array_pop($lines) . $held;

            yield from $lines;
        }

        if ($buffer !== '') {
            yield \rtrim($buffer, "\r");
        }
    }
}
