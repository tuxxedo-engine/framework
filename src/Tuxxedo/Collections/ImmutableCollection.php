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

namespace Tuxxedo\Collections;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements CollectionInterface<TKey, TValue>
 */
class ImmutableCollection implements CollectionInterface
{
    /**
     * @var array<TKey, TValue>
     */
    protected array $collection;

    /**
     * @param array<TKey, TValue> $collection
     */
    public function __construct(
        array $collection = [],
    ) {
        $this->collection = $collection;
    }

    public function count(): int
    {
        return \sizeof($this->collection);
    }

    /**
     * @return TValue
     */
    public function current(): mixed
    {
        /** @var TValue */
        return \current($this->collection);
    }

    public function next(): void
    {
        \next($this->collection);
    }

    /**
     * @return TKey
     */
    public function key(): mixed
    {
        /** @var TKey */
        return \key($this->collection);
    }

    public function valid(): bool
    {
        return \key($this->collection) !== null;
    }

    public function rewind(): void
    {
        \reset($this->collection);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->collection[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->collection[$offset];
    }

    /**
     * @return never
     *
     * @throws ImmutableException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw ImmutableException::fromWriteViolation();
    }

    /**
     * @return never
     *
     * @throws ImmutableException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw ImmutableException::fromWriteViolation();
    }

    public function firstKey(): mixed
    {
        return \array_key_first($this->collection);
    }

    public function lastKey(): mixed
    {
        return \array_key_last($this->collection);
    }

    public function first(): mixed
    {
        $offset = $this->firstKey();

        if ($offset !== null) {
            return $this->collection[$offset];
        }

        return null;
    }

    public function last(): mixed
    {
        $offset = $this->lastKey();

        if ($offset !== null) {
            return $this->collection[$offset];
        }

        return null;
    }

    public function contains(
        mixed ...$values,
    ): bool {
        if (\sizeof($values) === 0) {
            return false;
        }

        foreach ($values as $value) {
            if ($value instanceof ImmutableCollection) {
                if (!$this->contains(...$value->toArray())) {
                    return false;
                }
            } elseif (!\in_array($value, $this->collection, true)) {
                return false;
            }
        }

        return true;
    }

    public function sort(): static
    {
        \asort($this->collection);

        return $this;
    }

    public function sortKeys(): static
    {
        \ksort($this->collection);

        return $this;
    }

    public function reverse(): static
    {
        $this->collection = \array_reverse(
            array: $this->collection,
            preserve_keys: true,
        );

        return $this;
    }

    public function reverseKeys(): static
    {
        \krsort($this->collection);

        return $this;
    }

    /**
     * @return TKey[]
     */
    public function keys(): array
    {
        return \array_keys($this->collection);
    }

    /**
     * @return TValue[]
     */
    public function values(): array
    {
        return \array_values($this->collection);
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->collection;
    }

    /**
     * @return Collection<TKey, TValue>
     */
    public function toMutable(): Collection
    {
        return new Collection($this->collection);
    }
}
