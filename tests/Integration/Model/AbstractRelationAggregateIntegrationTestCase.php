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
use Tuxxedo\Model\ModelException;

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
}
