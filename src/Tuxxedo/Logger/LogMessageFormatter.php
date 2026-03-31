<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Logger;

class LogMessageFormatter implements LogMessageFormatterInterface
{
    /**
     * @param array<string, scalar> $placeholders
     */
    public function format(
        string $message,
        array $placeholders = [],
        ?LogLevel $level = null,
        ?\DateTimeImmutable $timestamp = null,
    ): string {
        $replacements = [];
        $values = [];

        foreach ($placeholders as $placeholder => $value) {
            $replacements[] = '{' . $placeholder . '}';
            $values[] = \strval($value);
        }

        $message = \str_replace($replacements, $values, $message);

        if ($level !== null) {
            $message = $this->formatLogLevel($message, $level);
        }

        return $this->formatTimestamp($message, $timestamp ?? new \DateTimeImmutable()) . \PHP_EOL;
    }

    protected function formatLogLevel(
        string $message,
        LogLevel $level,
    ): string {
        return \sprintf(
            '[%s] %s',
            $level->name,
            $message,
        );
    }

    protected function formatTimestamp(
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
