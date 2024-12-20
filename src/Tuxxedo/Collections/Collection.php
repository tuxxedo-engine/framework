<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2024 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Collections;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements CollectionInterface<TKey, TValue>
 */
class Collection implements CollectionInterface
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

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->collection[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->collection[$offset]);
    }

    /**
     * @param \Closure(TValue): TValue $callback
     */
    public function map(
        \Closure $callback,
    ): static {
        $this->collection = \array_map($callback, $this->collection);

        return $this;
    }

    /**
     * @param \Closure(TValue): bool $callback
     */
    public function filter(
        \Closure $callback,
    ): static {
        $this->collection = \array_filter($this->collection, $callback);

        return $this;
    }

    /**
     * @param Collection<TKey, TValue> ...$collections
     */
    public function merge(
        CollectionInterface ...$collections,
    ): static {
        foreach ($collections as $collection) {
            $this->collection = \array_merge($this->collection, $collection->toArray());
        }

        return $this;
    }

    /**
     * @param TValue ...$values
     */
    public function append(
        mixed ...$values,
    ): static {
        \array_push($this->collection, ...$values);

        return $this;
    }

    /**
     * @param TValue ...$values
     */
    public function prepend(
        mixed ...$values,
    ): static {
        \array_unshift($this->collection, ...$values);

        return $this;
    }

    public function pop(): static
    {
        \array_pop($this->collection);

        return $this;
    }

    public function shift(): static
    {
        \array_shift($this->collection);

        return $this;
    }

    public function clear(): static
    {
        $this->collection = [];

        return $this;
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
            if ($value instanceof Collection) {
                if (!$this->contains(...$value->toArray())) {
                    return false;
                }
            } elseif (!\in_array($value, $this->collection)) {
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
     * @return ImmutableCollection<TKey, TValue>
     */
    public function toImmutable(): ImmutableCollection
    {
        return new ImmutableCollection($this->collection);
    }
}