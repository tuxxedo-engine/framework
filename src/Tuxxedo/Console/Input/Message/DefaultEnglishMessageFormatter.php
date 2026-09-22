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

namespace Tuxxedo\Console\Input\Message;

class DefaultEnglishMessageFormatter implements MessageFormatterInterface
{
    public function forConfirmIndicator(
        bool $default,
    ): string {
        return $default
            ? '[Y/n]'
            : '[y/N]';
    }

    public function parseConfirmAnswer(
        string $normalized,
    ): ?bool {
        if ($normalized === 'y' || $normalized === 'yes') {
            return true;
        }

        if ($normalized === 'n' || $normalized === 'no') {
            return false;
        }

        return null;
    }

    public function forDefaultSuffix(
        string $default,
    ): string {
        return \sprintf(' [default: %s]', $default);
    }

    public function forInvalidChoice(): string
    {
        return 'Invalid choice, please try again.';
    }

    public function forEmptyAnswerNotAllowed(): string
    {
        return 'A value is required.';
    }

    public function forEofOnRequiredInput(): string
    {
        return 'Reached end of input before a required value was provided.';
    }
}
