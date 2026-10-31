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

namespace Integration\Model;

use Fixture\Model\Aggregate\AggregateNote;
use Fixture\Model\Aggregate\AggregateOrder;
use Fixture\Model\Aggregate\AggregateOwner;
use Fixture\Model\Aggregate\AggregatePolyTag;
use Fixture\Model\Aggregate\AggregateTag;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\RelationSubqueryInterface;

abstract class AbstractRelationAggregateIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    private int $ownerOneId = 0;
    private int $ownerTwoId = 0;
    private int $ownerEmptyId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAggregateOwnersTable();
        $this->createAggregateOrdersTable();
        $this->createAggregateTagsTable();
        $this->createAggregateOwnerTagPivotTable();
        $this->createAggregateNotesTable();
        $this->createAggregatePolyTagsTable();
        $this->createAggregateOwnerPolyTagPivotTable();

        $this->seedFixtures();
    }

    private function seedFixtures(): void
    {
        $ownerOne = new AggregateOwner();
        $ownerOne->label = 'one';
        $savedOne = $this->modelsManager->save($ownerOne);
        $this->ownerOneId = $savedOne->id ?? throw new \LogicException('owner one id missing');

        $ownerTwo = new AggregateOwner();
        $ownerTwo->label = 'two';
        $savedTwo = $this->modelsManager->save($ownerTwo);
        $this->ownerTwoId = $savedTwo->id ?? throw new \LogicException('owner two id missing');

        $ownerEmpty = new AggregateOwner();
        $ownerEmpty->label = 'empty';
        $savedEmpty = $this->modelsManager->save($ownerEmpty);
        $this->ownerEmptyId = $savedEmpty->id ?? throw new \LogicException('owner empty id missing');

        foreach ([10, 20, 30] as $amount) {
            $order = new AggregateOrder();
            $order->ownerId = $this->ownerOneId;
            $order->amount = $amount;
            (void) $this->modelsManager->save($order);
        }

        foreach ([100, 200] as $amount) {
            $order = new AggregateOrder();
            $order->ownerId = $this->ownerTwoId;
            $order->amount = $amount;
            (void) $this->modelsManager->save($order);
        }

        $tagIds = [];

        foreach (['alpha', 'beta', 'gamma'] as $label) {
            $tag = new AggregateTag();
            $tag->label = $label;
            $savedTag = $this->modelsManager->save($tag);
            $tagIds[] = $savedTag->id ?? throw new \LogicException('tag id missing');
        }

        $this->seedOwnerTagPivot($this->ownerOneId, $tagIds[0]);
        $this->seedOwnerTagPivot($this->ownerOneId, $tagIds[1]);
        $this->seedOwnerTagPivot($this->ownerTwoId, $tagIds[2]);

        foreach ([5, 15] as $length) {
            $note = new AggregateNote();
            $note->hostType = AggregateOwner::class;
            $note->hostId = $this->ownerOneId;
            $note->length = $length;
            (void) $this->modelsManager->save($note);
        }

        $note = new AggregateNote();
        $note->hostType = AggregateOwner::class;
        $note->hostId = $this->ownerTwoId;
        $note->length = 100;
        (void) $this->modelsManager->save($note);

        foreach ([['color', 'red', 5], ['color', 'green', 15], ['size', 'large', 100]] as $tagSpec) {
            $polyTag = new AggregatePolyTag();
            $polyTag->realm = $tagSpec[0];
            $polyTag->code = $tagSpec[1];
            $polyTag->label = $tagSpec[0] . '.' . $tagSpec[1];
            $polyTag->weight = $tagSpec[2];
            (void) $this->modelsManager->save($polyTag);
        }

        $this->seedOwnerPolyTagPivot($this->ownerOneId, 'color', 'red');
        $this->seedOwnerPolyTagPivot($this->ownerOneId, 'color', 'green');
        $this->seedOwnerPolyTagPivot($this->ownerTwoId, 'size', 'large');
    }

    public function testWithCountPopulatesCountSlotForHasMany(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withCount(relationName: 'orders')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(3, $byId[$this->ownerOneId]->ordersCount);
        self::assertSame(2, $byId[$this->ownerTwoId]->ordersCount);
    }

    public function testWithSumPopulatesSumSlot(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withSum(relationName: 'orders', column: 'amount')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(60, $byId[$this->ownerOneId]->ordersTotal);
        self::assertSame(300, $byId[$this->ownerTwoId]->ordersTotal);
    }

    public function testWithAvgPopulatesAvgSlot(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withAvg(relationName: 'orders', column: 'amount')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertNotNull($byId[$this->ownerOneId]->ordersAverage);
        self::assertNotNull($byId[$this->ownerTwoId]->ordersAverage);
        self::assertEqualsWithDelta(20.0, $byId[$this->ownerOneId]->ordersAverage, 0.001);
        self::assertEqualsWithDelta(150.0, $byId[$this->ownerTwoId]->ordersAverage, 0.001);
    }

    public function testWithMinPopulatesMinSlot(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withMin(relationName: 'orders', column: 'amount')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(10, $byId[$this->ownerOneId]->ordersMin);
        self::assertSame(100, $byId[$this->ownerTwoId]->ordersMin);
    }

    public function testWithMaxPopulatesMaxSlot(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withMax(relationName: 'orders', column: 'amount')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(30, $byId[$this->ownerOneId]->ordersMax);
        self::assertSame(200, $byId[$this->ownerTwoId]->ordersMax);
    }

    public function testComposedAggregatesAcrossRelationTypes(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withCount(relationName: 'orders')
            ->withSum(relationName: 'orders', column: 'amount')
            ->withCount(relationName: 'tags')
            ->withCount(relationName: 'notes')
            ->withSum(relationName: 'notes', column: 'length')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(3, $byId[$this->ownerOneId]->ordersCount);
        self::assertSame(60, $byId[$this->ownerOneId]->ordersTotal);
        self::assertSame(2, $byId[$this->ownerOneId]->tagsCount);
        self::assertSame(2, $byId[$this->ownerOneId]->notesCount);
        self::assertSame(20, $byId[$this->ownerOneId]->notesLengthSum);

        self::assertSame(2, $byId[$this->ownerTwoId]->ordersCount);
        self::assertSame(300, $byId[$this->ownerTwoId]->ordersTotal);
        self::assertSame(1, $byId[$this->ownerTwoId]->tagsCount);
        self::assertSame(1, $byId[$this->ownerTwoId]->notesCount);
        self::assertSame(100, $byId[$this->ownerTwoId]->notesLengthSum);
    }

    public function testWithCountCallbackOnlyCountsMatchingRows(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withCount(
                relationName: 'orders',
                callback: static function (RelationSubqueryInterface $orders): void {
                    $orders->where('aggregate_orders.amount', 20, '>=');
                },
            )
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(2, $byId[$this->ownerOneId]->ordersCount);
        self::assertSame(2, $byId[$this->ownerTwoId]->ordersCount);
        self::assertSame(0, $byId[$this->ownerEmptyId]->ordersCount);
    }

    public function testWithMinCallbackOnlyAggregatesMatchingRows(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withMin(
                relationName: 'orders',
                column: 'amount',
                callback: static function (RelationSubqueryInterface $orders): void {
                    $orders->where('aggregate_orders.amount', 15, '>=');
                },
            )
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(20, $byId[$this->ownerOneId]->ordersMin);
        self::assertSame(100, $byId[$this->ownerTwoId]->ordersMin);
        self::assertNull($byId[$this->ownerEmptyId]->ordersMin);
    }

    public function testOrderByCountOrdersByRelationCount(): void
    {
        self::assertSame(
            [
                'one',
                'two',
                'empty',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->orderByCount(
                        relationName: 'orders',
                        direction: OrderDirection::DESC,
                    ),
            ),
        );
    }

    public function testOrderByMinWithCallbackPutsMissingValuesLast(): void
    {
        $atLeastFifty = static function (RelationSubqueryInterface $orders): void {
            $orders->where('aggregate_orders.amount', 50, '>=');
        };

        foreach ([OrderDirection::ASC, OrderDirection::DESC] as $direction) {
            self::assertSame(
                [
                    'two',
                    'one',
                    'empty',
                ],
                $this->labels(
                    $this->modelsManager
                        ->query(AggregateOwner::class)
                        ->orderByMin(
                            relationName: 'orders',
                            column: 'amount',
                            direction: $direction,
                            callback: $atLeastFifty,
                        )
                        ->orderBy('id'),
                ),
            );
        }
    }

    public function testOrderByAvgOrdersByRelationAverage(): void
    {
        self::assertSame(
            [
                'one',
                'two',
                'empty',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->orderByAvg(
                        relationName: 'orders',
                        column: 'amount',
                    ),
            ),
        );
    }

    public function testOrderByMaxAcceptsStringDirection(): void
    {
        self::assertSame(
            [
                'two',
                'one',
                'empty',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->orderByMax(
                        relationName: 'orders',
                        column: 'amount',
                        direction: 'DESC',
                    ),
            ),
        );
    }

    public function testOrderByAggregateOnLoadedRelation(): void
    {
        $owner = $this->modelsManager->fetchById(
            class: AggregateOwner::class,
            id: $this->ownerOneId,
        );

        self::assertNotNull($owner->orders);

        $amounts = [];

        foreach (
            $owner->orders
                ->orderByMax(
                    relationName: 'owner',
                    column: 'id',
                )
                ->orderBy('amount', OrderDirection::DESC) as $order
        ) {
            $amounts[] = $order->amount;
        }

        self::assertSame(
            [
                30,
                20,
                10,
            ],
            $amounts,
        );
    }

    public function testOrWhereInsideWhereHasCallbackStaysCorrelated(): void
    {
        self::assertSame(
            [
                'one',
                'two',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->whereHas(
                        relationName: 'orders',
                        callback: static function (RelationSubqueryInterface $orders): void {
                            $orders
                                ->where('aggregate_orders.amount', 10)
                                ->orWhere('aggregate_orders.amount', 200);
                        },
                    )
                    ->orderBy('id'),
            ),
        );
    }

    public function testWhereHasCallbackWithoutConditionsMatchesPlainWhereHas(): void
    {
        self::assertSame(
            [
                'one',
                'two',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->whereHas(
                        relationName: 'orders',
                        callback: static function (RelationSubqueryInterface $orders): void {
                        },
                    )
                    ->orderBy('id'),
            ),
        );
    }

    public function testNestedOrWhereHasInsideRelationCallback(): void
    {
        self::assertSame(
            [
                $this->ownerOneId,
                $this->ownerTwoId,
            ],
            $this->ownerIds(
                $this->modelsManager
                    ->query(AggregateOrder::class)
                    ->whereHas(
                        relationName: 'owner',
                        callback: static function (RelationSubqueryInterface $owner): void {
                            $owner
                                ->where('aggregate_owners.label', 'one')
                                ->orWhereHas(
                                    relationName: 'tags',
                                    callback: static function (RelationSubqueryInterface $tags): void {
                                        $tags->where('aggregate_tags.label', 'gamma');
                                    },
                                );
                        },
                    ),
            ),
        );
    }

    public function testNestedOrWhereDoesntHaveInsideRelationCallback(): void
    {
        self::assertSame(
            [
                $this->ownerOneId,
            ],
            $this->ownerIds(
                $this->modelsManager
                    ->query(AggregateOrder::class)
                    ->whereHas(
                        relationName: 'owner',
                        callback: static function (RelationSubqueryInterface $owner): void {
                            $owner
                                ->where('aggregate_owners.label', 'missing')
                                ->orWhereDoesntHave(
                                    relationName: 'tags',
                                    callback: static function (RelationSubqueryInterface $tags): void {
                                        $tags->where('aggregate_tags.label', 'gamma');
                                    },
                                );
                        },
                    ),
            ),
        );
    }

    public function testOrderBySumKeepsCallOrderWithPlainOrderBy(): void
    {
        self::assertSame(
            [
                'empty',
                'one',
                'two',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->orderBy('label')
                    ->orderBySum(
                        relationName: 'orders',
                        column: 'amount',
                        direction: OrderDirection::DESC,
                    ),
            ),
        );
    }

    public function testWhereHasWithCountThreshold(): void
    {
        self::assertSame(
            [
                'one',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->whereHas(
                        relationName: 'orders',
                        count: 3,
                    ),
            ),
        );

        self::assertSame(
            [
                'two',
                'empty',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->whereHas(
                        relationName: 'orders',
                        operator: '<',
                        count: 3,
                    )
                    ->orderBy('id'),
            ),
        );
    }

    public function testWhereHasCountThresholdAppliesCallback(): void
    {
        self::assertSame(
            [
                'one',
                'two',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->whereHas(
                        relationName: 'orders',
                        callback: static function (RelationSubqueryInterface $orders): void {
                            $orders->where('aggregate_orders.amount', 20, '>=');
                        },
                        count: 2,
                    )
                    ->orderBy('id'),
            ),
        );
    }

    public function testWhereHasCountThresholdOnNestedPathThrows(): void
    {
        try {
            (void) $this->modelsManager
                ->query(AggregateOwner::class)
                ->whereHas(
                    relationName: 'orders.owner',
                    count: 2,
                );

            self::fail('Expected ModelException for a count threshold on a nested path');
        } catch (ModelException $exception) {
            self::assertStringContainsString('nested paths are not supported', $exception->getMessage());
        }
    }

    public function testOrWhereHasCombinesWithOr(): void
    {
        self::assertSame(
            [
                'two',
                'empty',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->where('label', 'empty')
                    ->orWhereHas(
                        relationName: 'orders',
                        callback: static function (RelationSubqueryInterface $orders): void {
                            $orders->where('aggregate_orders.amount', 100, '>=');
                        },
                    )
                    ->orderBy('id'),
            ),
        );
    }

    public function testOrWhereDoesntHaveCombinesWithOr(): void
    {
        self::assertSame(
            [
                'one',
                'empty',
            ],
            $this->labels(
                $this->modelsManager
                    ->query(AggregateOwner::class)
                    ->where('label', 'one')
                    ->orWhereDoesntHave('orders')
                    ->orderBy('id'),
            ),
        );
    }

    public function testWithoutAggregateCallLeavesSlotsNull(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertNull($byId[$this->ownerOneId]->ordersCount);
        self::assertNull($byId[$this->ownerOneId]->ordersTotal);
        self::assertNull($byId[$this->ownerOneId]->tagsCount);
        self::assertNull($byId[$this->ownerOneId]->notesCount);
    }

    public function testSumOverEmptyChildSetLeavesSlotNull(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withSum(relationName: 'orders', column: 'amount')
            ->withMin(relationName: 'orders', column: 'amount')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertNull($byId[$this->ownerEmptyId]->ordersTotal);
        self::assertNull($byId[$this->ownerEmptyId]->ordersMin);
    }

    public function testWithCountOnMorphToPropertyThrows(): void
    {
        try {
            (void) $this->modelsManager
                ->query(AggregateNote::class)
                ->withCount(relationName: 'host');

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'MorphTo aggregates are not supported',
                $exception->getMessage(),
            );
        }
    }

    public function testWithSumPopulatesMorphToManySlot(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withSum(relationName: 'polyTags', column: 'weight')
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(20, $byId[$this->ownerOneId]->polyTagsWeight);
        self::assertSame(100, $byId[$this->ownerTwoId]->polyTagsWeight);
        self::assertNull($byId[$this->ownerEmptyId]->polyTagsWeight);
    }

    public function testWithCountOnMorphToManyWithoutMatchingSlotThrows(): void
    {
        try {
            (void) $this->modelsManager
                ->query(AggregateOwner::class)
                ->withCount(relationName: 'polyTags');

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'No relation aggregate declared',
                $exception->getMessage(),
            );
            self::assertStringContainsString(
                'polyTags_count',
                $exception->getMessage(),
            );
        }
    }

    public function testWithMaxOnRelationWithoutMatchingSlotThrows(): void
    {
        try {
            (void) $this->modelsManager
                ->query(AggregateOwner::class)
                ->withMax(relationName: 'orders', column: 'nonexistent_column');

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'No relation aggregate declared',
                $exception->getMessage(),
            );
            self::assertStringContainsString(
                'orders_max_nonexistent_column',
                $exception->getMessage(),
            );
        }
    }

    public function testExplicitAliasWinsOverDeclaredSlot(): void
    {
        $owners = \iterator_to_array($this->modelsManager
            ->query(AggregateOwner::class)
            ->withCount(
                relationName: 'orders',
                alias: 'orders_count',
            )
            ->fetchAll());

        $byId = $this->indexById($owners);

        self::assertSame(3, $byId[$this->ownerOneId]->ordersCount);
    }

    private function seedOwnerTagPivot(
        int $ownerId,
        int $tagId,
    ): void {
        $this->connection->insert(
            table: 'aggregate_owner_tag',
        )
            ->set(column: 'owner_id', value: $ownerId)
            ->set(column: 'tag_id', value: $tagId)
            ->execute();
    }

    private function seedOwnerPolyTagPivot(
        int $ownerId,
        string $polyTagRealm,
        string $polyTagCode,
    ): void {
        $this->connection->insert(
            table: 'aggregate_owner_poly_tag',
        )
            ->set(column: 'owner_type', value: AggregateOwner::class)
            ->set(column: 'owner_id', value: $ownerId)
            ->set(column: 'poly_tag_realm', value: $polyTagRealm)
            ->set(column: 'poly_tag_code', value: $polyTagCode)
            ->execute();
    }

    /**
     * @param iterable<AggregateOwner> $owners
     * @return array<int, AggregateOwner>
     */
    private function indexById(
        iterable $owners,
    ): array {
        $indexed = [];

        foreach ($owners as $owner) {
            self::assertNotNull($owner->id);
            $indexed[$owner->id] = $owner;
        }

        return $indexed;
    }

    /**
     * @param iterable<AggregateOwner> $owners
     * @return list<string>
     */
    private function labels(
        iterable $owners,
    ): array {
        $labels = [];

        foreach ($owners as $owner) {
            $labels[] = $owner->label;
        }

        return $labels;
    }

    /**
     * @param iterable<AggregateOrder> $orders
     * @return list<int>
     */
    private function ownerIds(
        iterable $orders,
    ): array {
        $ids = [];

        foreach ($orders as $order) {
            $ids[$order->ownerId] = $order->ownerId;
        }

        \sort($ids);

        return $ids;
    }
}
