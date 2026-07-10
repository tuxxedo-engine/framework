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

namespace Support\Http;

class PhpInputStreamWrapper
{
    public static string $content = '';

    private int $position = 0;

    public mixed $context = null;

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath,
    ): bool {
        $this->position = 0;

        return true;
    }

    public function stream_read(
        int $count,
    ): string {
        $chunk = \substr(self::$content, $this->position, $count);
        $this->position += \strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= \strlen(self::$content);
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }

    /**
     * @return array<string, int>
     */
    public function url_stat(
        string $path,
        int $flags,
    ): array {
        return [];
    }
}
