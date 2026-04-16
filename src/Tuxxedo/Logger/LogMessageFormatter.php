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

namespace Tuxxedo\Logger;

class LogMessageFormatter implements LogMessageFormatterInterface
{
    public function interpolate(
        string $message,
        array $placeholders,
    ): string {
        $replacements = [];
        $values = [];

        foreach ($placeholders as $placeholder => $value) {
            $replacements[] = '{' . $placeholder . '}';
            $values[] = \strval($value);
        }

        return \str_replace($replacements, $values, $message);
    }

    /**
     * @param array<string, scalar> $placeholders
     */
    public function format(
        string $message,
        array $placeholders = [],
        ?LogLevel $level = null,
        ?\DateTimeImmutable $timestamp = null,
    ): string {
        $message = $this->interpolate($message, $placeholders);

        if ($level !== null) {
            $message = $this->formatLogLevel($message, $level);
        }

        return $this->formatTimestamp($message, $timestamp ?? new \DateTimeImmutable()) . \PHP_EOL;
    }

    public function formatLogLevel(
        string $message,
        LogLevel $level,
    ): string {
        return \sprintf(
            '[%s] %s',
            $level->name,
            $message,
        );
    }

    public function formatTimestamp(
        string $message,
        \DateTimeImmutable $timestamp,
    ): string {
        return \sprintf(
            '[%s] %s',
            $timestamp->format('Y-m-d\TH:i:s.uP'),
            $message,
        );
    }
}
