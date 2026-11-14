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

use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;

/**
 * @template TModel of object
 *
 * @extends AbstractQueryable<TModel>
 * @implements RelationInterface<TModel>
 */
class Relation extends AbstractQueryable implements RelationInterface
{
    /**
     * @var array<string, array{item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null, pivot: array<string, mixed>}>
     */
    public private(set) array $pendingAdds = [];

    /**
     * @var array<string, array{item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null, pivot: array<string, mixed>}>
     */
    public private(set) array $pendingRemoves = [];

    /**
     * @var array<string, array{item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null, pivot: array<string, mixed>}>|null
     */
    public private(set) ?array $pendingSync = null;

    /**
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string|SelectStatementInterface, direction: OrderDirection}>, ?int, ?int): iterable<int, TModel>)|null $loaderBuilder
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): int)|null $countBuilder
     * @param array<int, TModel>|null $prefetched
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string|SelectStatementInterface, direction: OrderDirection}> $orderBy
     * @param ?class-string<TModel> $modelClass
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): SelectStatementInterface)|null $statementBuilder
     */
    final private function __construct(
        ?\Closure $loaderBuilder = null,
        ?\Closure $countBuilder = null,
        private readonly ?array $prefetched = null,
        array $criteriaStack = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null,
        ?ModelsManagerInterface $manager = null,
        ?string $modelClass = null,
        ?\Closure $statementBuilder = null,
        public readonly ?RelationPivotInterface $pivot = null,
    ) {
        parent::__construct(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            criteriaStack: $criteriaStack,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
            manager: $manager,
            modelClass: $modelClass,
            statementBuilder: $statementBuilder,
        );
    }

    /**
     * @template TItem of object
     *
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string|SelectStatementInterface, direction: OrderDirection}>, ?int, ?int): iterable<int, TItem> $loaderBuilder
     * @param \Closure(list<\Closure(WhereStatementInterface): void>): int $countBuilder
     * @param ?class-string<TItem> $modelClass
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): SelectStatementInterface)|null $statementBuilder
     * @return self<TItem>
     */
    public static function createFromBuilder(
        \Closure $loaderBuilder,
        \Closure $countBuilder,
        ?ModelsManagerInterface $manager = null,
        ?string $modelClass = null,
        ?\Closure $statementBuilder = null,
        ?RelationPivotInterface $pivot = null,
    ): self {
        return new self(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            manager: $manager,
            modelClass: $modelClass,
            statementBuilder: $statementBuilder,
            pivot: $pivot,
        );
    }

    /**
     * @template TItem of object
     *
     * @param array<int, TItem> $values
     * @param ?class-string<TItem> $modelClass
     * @return self<TItem>
     */
    public static function createFromPrefetched(
        array $values,
        ?ModelsManagerInterface $manager = null,
        ?string $modelClass = null,
        ?RelationPivotInterface $pivot = null,
    ): self {
        return new self(
            prefetched: $values,
            manager: $manager,
            modelClass: $modelClass,
            pivot: $pivot,
        );
    }

    /**
     * @template TItem of object
     *
     * @param array<int, TItem> $prefetched
     * @param \Closure(list<\Closure(WhereStatementInterface): void>, list<array{column: string|SelectStatementInterface, direction: OrderDirection}>, ?int, ?int): iterable<int, TItem> $loaderBuilder
     * @param \Closure(list<\Closure(WhereStatementInterface): void>): int $countBuilder
     * @param list<\Closure(WhereStatementInterface): void> $initialCriteriaStack
     * @param list<array{column: string|SelectStatementInterface, direction: OrderDirection}> $initialOrderBy
     * @param ?class-string<TItem> $modelClass
     * @param (\Closure(list<\Closure(WhereStatementInterface): void>): SelectStatementInterface)|null $statementBuilder
     * @return self<TItem>
     */
    public static function createFromPrefetchedWithBuilder(
        array $prefetched,
        \Closure $loaderBuilder,
        \Closure $countBuilder,
        array $initialCriteriaStack = [],
        array $initialOrderBy = [],
        ?int $initialLimit = null,
        ?int $initialOffset = null,
        ?ModelsManagerInterface $manager = null,
        ?string $modelClass = null,
        ?\Closure $statementBuilder = null,
        ?RelationPivotInterface $pivot = null,
    ): self {
        return new self(
            loaderBuilder: $loaderBuilder,
            countBuilder: $countBuilder,
            prefetched: $prefetched,
            criteriaStack: $initialCriteriaStack,
            orderBy: $initialOrderBy,
            limit: $initialLimit,
            offset: $initialOffset,
            manager: $manager,
            modelClass: $modelClass,
            statementBuilder: $statementBuilder,
            pivot: $pivot,
        );
    }

    /**
     * @param list<\Closure(WhereStatementInterface): void> $criteriaStack
     * @param list<array{column: string|SelectStatementInterface, direction: OrderDirection}> $orderBy
     */
    protected function cloneWith(
        array $criteriaStack,
        array $orderBy,
        ?int $limit,
        ?int $offset,
    ): static {
        return new static(
            loaderBuilder: $this->loaderBuilder,
            countBuilder: $this->countBuilder,
            prefetched: $this->loaderBuilder !== null
                ? null
                : $this->prefetched,
            criteriaStack: $criteriaStack,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
            manager: $this->manager,
            modelClass: $this->modelClass,
            statementBuilder: $this->statementBuilder,
            pivot: $this->pivot,
        );
    }

    public function add(
        object|array|int|string $item,
        array $pivot = [],
    ): void {
        if ($pivot !== []) {
            $this->requirePivot('pivot values')->validatePivotValues($pivot);
        }

        $entry = $this->entryFor($item);
        $identity = $entry['identity'];

        if (isset($this->pendingRemoves[$identity])) {
            unset($this->pendingRemoves[$identity]);

            if ($this->pivot === null || $entry['key'] === null) {
                return;
            }
        }

        $this->pendingAdds[$identity] = [
            'item' => $entry['item'] ?? $this->pendingAdds[$identity]['item'] ?? null,
            'key' => $entry['key'],
            'pivot' => [
                ...$this->pendingAdds[$identity]['pivot'] ?? [],
                ...$pivot,
            ],
        ];
    }

    public function remove(
        object|array|int|string $item,
    ): void {
        $entry = $this->entryFor($item);
        $identity = $entry['identity'];

        if (isset($this->pendingAdds[$identity])) {
            unset($this->pendingAdds[$identity]);

            if ($this->pivot === null) {
                return;
            }
        }

        if ($this->pendingSync !== null) {
            unset($this->pendingSync[$identity]);
        }

        if ($this->pivot !== null && $entry['key'] === null) {
            return;
        }

        $this->pendingRemoves[$identity] = [
            'item' => $entry['item'],
            'key' => $entry['key'],
            'pivot' => [],
        ];
    }

    public function sync(
        iterable $items,
    ): void {
        $this->requirePivot('sync()');

        $sync = [];

        foreach ($items as $item) {
            $entry = $this->entryFor($item);

            $sync[$entry['identity']] = [
                'item' => $entry['item'],
                'key' => $entry['key'],
                'pivot' => [],
            ];
        }

        $this->pendingSync = $sync;
        $this->pendingAdds = [];
        $this->pendingRemoves = [];
    }

    #[\NoDiscard]
    public function pivotFor(
        object|array|int|string $item,
    ): ?object {
        $pivot = $this->requirePivot('pivotFor()');
        $key = $pivot->keyOf($item);

        if ($key === null) {
            return null;
        }

        return $pivot->pivots()[$pivot->hash($key)] ?? null;
    }

    public function hasPendingChanges(): bool
    {
        return $this->pendingAdds !== [] ||
            $this->pendingRemoves !== [] ||
            $this->pendingSync !== null;
    }

    public function clearPending(): void
    {
        $this->pendingAdds = [];
        $this->pendingRemoves = [];
        $this->pendingSync = null;

        $this->pivot?->reset();
    }

    /**
     * @return TModel|null
     */
    #[\NoDiscard]
    public function first(): ?object
    {
        if ($this->hasPendingChanges() || $this->prefetched !== null) {
            foreach ($this->materialize() as $item) {
                return $item;
            }

            return null;
        }

        return parent::first();
    }

    public function isMaterialized(): bool
    {
        return parent::isMaterialized() || $this->prefetched !== null;
    }

    protected function computeTotalCount(): int
    {
        if ($this->prefetched !== null) {
            return \sizeof($this->prefetched);
        }

        return parent::computeTotalCount();
    }

    /**
     * @return array<int, TModel>
     */
    protected function materialize(): array
    {
        $base = $this->loadBase();

        if (!$this->hasPendingChanges()) {
            return $base;
        }

        /** @var array<string, TModel|null> $items */
        $items = [];

        foreach ($base as $item) {
            $items[$this->entryFor($item)['identity']] = $item;
        }

        if ($this->pendingSync !== null) {
            $synced = [];

            foreach ($this->pendingSync as $identity => $entry) {
                $synced[$identity] = $items[$identity] ?? $entry['item'];
            }

            $items = $synced;
        }

        foreach ($this->pendingRemoves as $identity => $entry) {
            unset($items[$identity]);
        }

        foreach ($this->pendingAdds as $identity => $entry) {
            $items[$identity] ??= $entry['item'];
        }

        $missing = [];
        $keysByHash = [];

        foreach ($items as $identity => $item) {
            $key = $this->pendingAdds[$identity]['key'] ?? $this->pendingSync[$identity]['key'] ?? null;

            if ($item === null && $key !== null && $this->pivot !== null) {
                $missing[$identity] = $key;
                $keysByHash[$this->pivot->hash($key)] = $key;
            }
        }

        if ($keysByHash !== [] && $this->pivot !== null) {
            $resolved = $this->pivot->resolve($keysByHash);

            foreach ($missing as $identity => $key) {
                /** @var TModel|null $target */
                $target = $resolved[$this->pivot->hash($key)] ?? null;
                $items[$identity] = $target;
            }
        }

        return \array_values(
            \array_filter(
                $items,
                static fn (?object $item): bool => $item !== null,
            ),
        );
    }

    /**
     * @return array<int, TModel>
     */
    protected function loadBase(): array
    {
        if ($this->prefetched !== null) {
            return $this->prefetched;
        }

        return parent::loadBase();
    }

    /**
     * @param TModel|\BackedEnum|array<string, int|string>|int|string $item
     * @return array{identity: string, item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null}
     *
     * @throws ModelException
     */
    private function entryFor(
        object|array|int|string $item,
    ): array {
        if (\is_object($item) && !$item instanceof \BackedEnum) {
            $key = $this->pivot?->keyOf($item);

            /** @var TModel $item */
            return [
                'identity' => $key !== null
                    ? 'k:' . $this->pivot->hash($key)
                    : 'o:' . \spl_object_id($item),
                'item' => $item,
                'key' => $key,
            ];
        }

        $pivot = $this->requirePivot('keys');
        $key = $pivot->keyOf($item);

        if ($key === null) {
            // @codeCoverageIgnoreStart
            throw ModelException::fromInvalidRelationKey(
                relatedClass: $pivot->relatedClass,
                expected: 'a key value',
                actual: \get_debug_type($item),
            );
            // @codeCoverageIgnoreEnd
        }

        return [
            'identity' => 'k:' . $pivot->hash($key),
            'item' => null,
            'key' => $key,
        ];
    }

    /**
     * @throws ModelException
     */
    private function requirePivot(
        string $operation,
    ): RelationPivotInterface {
        return $this->pivot ?? throw ModelException::fromRelationRequiresPivot(
            relatedClass: $this->modelClass ?? 'object',
            operation: $operation,
        );
    }
}
