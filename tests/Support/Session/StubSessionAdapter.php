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

namespace Support\Session;

use Tuxxedo\Session\SessionAdapterInterface;
use Tuxxedo\Session\SessionException;
use Tuxxedo\Session\SessionStartMode;

class StubSessionAdapter implements SessionAdapterInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $data;

    public string $identifier;

    public int $regenerateCount = 0;

    private bool $started = false;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly SessionStartMode $startMode = SessionStartMode::EXPLICIT,
        array $data = [],
        string $identifier = 'stub-session-id',
    ) {
        $this->data = $data;
        $this->identifier = $identifier;

        if ($this->startMode === SessionStartMode::AUTO) {
            $this->start();
        }
    }

    private function startCheck(): void
    {
        if ($this->started) {
            return;
        }

        if ($this->startMode === SessionStartMode::EXPLICIT) {
            throw SessionException::fromNotStarted();
        }

        $this->start();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function start(): static
    {
        $this->started = true;

        return $this;
    }

    public function stop(): static
    {
        $this->started = false;
        $this->data = [];

        return $this;
    }

    public function restart(): static
    {
        return $this->stop()->start();
    }

    public function unset(): static
    {
        $this->startCheck();

        $this->data = [];

        return $this;
    }

    public function getIdentifier(): string
    {
        $this->startCheck();

        return $this->identifier;
    }

    public function regenerateIdentifier(): static
    {
        $this->startCheck();

        $this->regenerateCount++;
        $this->identifier = $this->identifier . '-' . $this->regenerateCount;

        return $this;
    }

    public function remove(
        string $name,
    ): void {
        $this->startCheck();

        unset($this->data[$name]);
    }

    public function has(
        string $name,
    ): bool {
        $this->startCheck();

        return \array_key_exists($name, $this->data);
    }

    public function set(
        string $name,
        mixed $value,
    ): static {
        $this->startCheck();

        if ($value instanceof \UnitEnum) {
            $value = $value->name;
        }

        $this->data[$name] = $value;

        return $this;
    }

    public function all(): array
    {
        $this->startCheck();

        return $this->data;
    }

    public function getRaw(
        string $name,
        mixed $default = null,
    ): mixed {
        $this->startCheck();

        if (!\array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->data[$name];
    }
}
