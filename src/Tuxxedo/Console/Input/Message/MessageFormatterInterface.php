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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: TranslatorMessageFormatter::class, lifecycle: Lifecycle::SINGLETON)]
interface MessageFormatterInterface
{
    public function forConfirmIndicator(
        bool $default,
    ): string;

    /**
     * @return array{yes: list<string>, no: list<string>}
     */
    public function confirmAnswers(): array;

    public function parseConfirmAnswer(
        string $answer,
    ): ?bool;

    public function forDefaultSuffix(
        string $default,
    ): string;

    public function forInvalidChoice(): string;

    public function forEmptyAnswerNotAllowed(): string;

    public function forEofOnRequiredInput(): string;
}
