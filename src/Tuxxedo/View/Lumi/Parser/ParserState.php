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

class ParserState implements ParserStateInterface
{
    public private(set) int $loopDepth = 0;
    public private(set) int $conditionDepth = 0;

    /**
     * @var string[]
     */
    public private(set) array $groupingDepth = [];

    /**
     * @var array<string, string|int|bool>
     */
    private array $state = [];

    public function enterLoop(): void
    {
        $this->loopDepth++;
    }

    public function leaveLoop(): void
    {
        if ($this->loopDepth === 0) {
            throw ParserException::fromUnexpectedLoopExit();
        }

        $this->loopDepth--;
    }

    public function enterCondition(): void
    {
        $this->conditionDepth++;
    }

    public function leaveCondition(): void
    {
        if ($this->conditionDepth === 0) {
            throw ParserException::fromUnexpectedConditionExit();
        }

        $this->conditionDepth--;
    }

    public function enterGrouping(
        string $name,
    ): void {
        \array_push($this->groupingDepth, $name);
    }

    public function leaveGrouping(
        string $name,
    ): void {
        if (\sizeof($this->groupingDepth) === 0) {
            throw ParserException::fromUnexpectedGroupingExit();
        }

        /** @var string $last */
        $last = \array_pop($this->groupingDepth);

        if ($last !== $name) {
            throw ParserException::fromUnexpectedNamedGroupingExit(
                name: $name,
                expectedName: $last,
            );
        }
    }

    public function isAllGroupingsClosed(): bool
    {
        return \sizeof($this->groupingDepth) === 0;
    }

    public function set(
        string $key,
        string|int|bool $value,
    ): self {
        $this->state[$key] = $value;

        return $this;
    }

    public function has(
        string $key,
    ): bool {
        return \array_key_exists($key, $this->state);
    }

    public function clear(
        string $key,
    ): self {
        unset($this->state[$key]);

        return $this;
    }

    public function getString(
        string $key,
    ): string {
        if (!$this->has($key)) {
            throw ParserException::fromMissingStateKey(
                key: $key,
            );
        }

        $value = $this->state[$key];

        if (!\is_string($value)) {
            throw ParserException::fromUnexpectedStateType(
                key: $key,
                type: \gettype($value),
                expectedType: 'string',
            );
        }

        return $value;
    }

    public function getInt(string $key): int
    {
        if (!$this->has($key)) {
            throw ParserException::fromMissingStateKey(
                key: $key,
            );
        }

        $value = $this->state[$key];

        if (!\is_int($value)) {
            throw ParserException::fromUnexpectedStateType(
                key: $key,
                type: \gettype($value),
                expectedType: 'int',
            );
        }

        return $value;
    }

    public function getBool(string $key): bool
    {
        if (!$this->has($key)) {
            throw ParserException::fromMissingStateKey(
                key: $key,
            );
        }

        $value = $this->state[$key];

        if (!\is_bool($value)) {
            throw ParserException::fromUnexpectedStateType(
                key: $key,
                type: \gettype($value),
                expectedType: 'bool',
            );
        }

        return $value;
    }
}
