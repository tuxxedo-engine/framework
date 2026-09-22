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

namespace Tuxxedo\Console\Input;

use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Stream\InputStreamInterface;

interface InputInterface
{
    public bool $isInteractive {
        get;
    }

    public InputStreamInterface $stream {
        get;
    }

    /**
     * @throws ConsoleException
     */
    public function readLine(): ?string;

    /**
     * @throws ConsoleException
     */
    public function readAll(): string;

    /**
     * @throws ConsoleException
     */
    public function prompt(
        string $question,
        ?string $default = null,
    ): string;

    /**
     * @throws ConsoleException
     */
    public function confirm(
        string $question,
        bool $default = false,
    ): bool;

    /**
     * @template TChoice
     *
     * @param list<TChoice> $choices
     * @return TChoice
     *
     * @throws ConsoleException
     */
    public function choose(
        string $question,
        array $choices,
    ): mixed;

    /**
     * @return array<string, mixed>
     *
     * @throws ConsoleException
     */
    public function ask(
        Questionnaire $questionnaire,
    ): array;
}
