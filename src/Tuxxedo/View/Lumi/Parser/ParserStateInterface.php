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

namespace Tuxxedo\View\Lumi\Parser;

interface ParserStateInterface
{
    public int $loopDepth {
        get;
    }

    public int $conditionDepth {
        get;
    }

    /**
     * @var string[]
     */
    public array $groupingStack {
        get;
    }

    public function enterLoop(): void;

    /**
     * @throws ParserException
     */
    public function leaveLoop(): void;

    public function enterCondition(): void;

    /**
     * @throws ParserException
     */
    public function leaveCondition(): void;

    public function enterGrouping(
        string $name,
    ): void;

    /**
     * @throws ParserException
     */
    public function leaveGrouping(
        string $name,
    ): void;

    public function isAllGroupingsClosed(): bool;

    public function set(
        string $key,
        string|int|bool $value,
    ): self;

    public function has(
        string $key,
    ): bool;

    public function clear(
        string $key,
    ): self;

    /**
     * @throws ParserException
     */
    public function getString(
        string $key,
    ): string;

    /**
     * @throws ParserException
     */
    public function getInt(
        string $key,
    ): int;

    /**
     * @throws ParserException
     */
    public function getBool(
        string $key,
    ): bool;
}
