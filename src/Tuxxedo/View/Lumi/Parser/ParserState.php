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
     * @var array<string, string|int|bool>
     */
    public private(set) array $state = [];

    /**
     * @var ParserStatePropertiesInterface[]
     */
    public private(set) array $stateStack = [];

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

    public function pushState(): void
    {
        \array_push($this->stateStack, ParserStateProperties::fromState($this));

        $this->conditionDepth = 0;
        $this->state = [];
    }

    public function popState(): void
    {
        $oldState = \array_pop($this->stateStack);

        if ($oldState === null) {
            throw ParserException::fromUnexpectedStackExit();
        }

        $this->conditionDepth = $oldState->conditionDepth;
        $this->state = $oldState->state;
    }

    public function isCleanState(
        bool $checkLoops = true,
        bool $checkConditions = true,
        bool $checkCustom = true,
    ): bool {
        if ($checkLoops && $this->loopDepth !== 0) {
            return false;
        }

        if ($checkConditions && $this->conditionDepth !== 0) {
            return false;
        }

        if ($checkCustom && \sizeof($this->state) !== 0) {
            return false;
        }

        return true;
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
