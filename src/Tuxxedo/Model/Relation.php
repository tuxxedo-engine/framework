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

namespace Tuxxedo\Model;

// @todo page(limit:, offset:) immutable, raw pagination — depends on generic Paginator
// @todo where() filter — narrowed where-builders (see criteria narrowing TODO on ModelsManagerInterface)
// @todo Eager-mode customization triggers lazy refetch (relevant once page()/where() land)
/**
 * @template TModel of object
 *
 * @implements RelationInterface<TModel>
 */
class Relation implements RelationInterface
{
    /**
     * @var array<int, TModel>|null
     */
    private ?array $cache = null;

    private ?int $cachedTotalCount = null;

    public int $totalCount {
        get {
            return $this->cachedTotalCount ??= $this->computeTotalCount();
        }
    }

    /**
     * @param (\Closure(): iterable<int, TModel>)|null $loader
     * @param (\Closure(): int)|null $countLoader
     * @param array<int, TModel>|null $prefetched
     */
    final private function __construct(
        private readonly ?\Closure $loader = null,
        private readonly ?\Closure $countLoader = null,
        private readonly ?array $prefetched = null,
    ) {
    }

    /**
     * @template TItem of object
     *
     * @param \Closure(): iterable<int, TItem> $loader
     * @param \Closure(): int $countLoader
     * @return self<TItem>
     */
    public static function createFromLoader(
        \Closure $loader,
        \Closure $countLoader,
    ): self {
        return new self(
            loader: $loader,
            countLoader: $countLoader,
        );
    }

    /**
     * @template TItem of object
     *
     * @param array<int, TItem> $values
     * @return self<TItem>
     */
    public static function createFromPrefetched(
        array $values,
    ): self {
        return new self(
            prefetched: $values,
        );
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->materialize()[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->materialize()[$offset];
    }

    /**
     * @return never
     *
     * @throws ModelException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw ModelException::fromImmutableRelation();
    }

    /**
     * @return never
     *
     * @throws ModelException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw ModelException::fromImmutableRelation();
    }

    public function getIterator(): \Generator
    {
        yield from $this->materialize();
    }

    public function count(): int
    {
        return \count($this->materialize());
    }

    public function isMaterialized(): bool
    {
        return $this->prefetched !== null || $this->cache !== null;
    }

    private function computeTotalCount(): int
    {
        if ($this->prefetched !== null) {
            return \count($this->prefetched);
        }

        if ($this->countLoader !== null) {
            return ($this->countLoader)();
        }

        return \count($this->materialize());
    }

    /**
     * @return array<int, TModel>
     */
    private function materialize(): array
    {
        if ($this->prefetched !== null) {
            return $this->prefetched;
        }

        if ($this->cache !== null) {
            return $this->cache;
        }

        if ($this->loader === null) {
            return [];
        }

        $loaded = ($this->loader)();

        return $this->cache = \is_array($loaded)
            ? $loaded
            : \iterator_to_array($loaded);
    }
}
