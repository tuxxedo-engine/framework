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

use Fixture\Model\Pivot\PivotCategory;
use Fixture\Model\Pivot\PivotCategoryProduct;
use Fixture\Model\Pivot\PivotLabelable;
use Fixture\Model\Pivot\PivotProduct;
use Fixture\Model\Priority;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

abstract class AbstractRelationPivotIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createPivotProductsTable();
        $this->createPivotCategoriesTable();
        $this->createPivotCategoryProductTable();
        $this->createPivotLabelablesTable();

        foreach (
            [
                1 => 'Shirt',
                2 => 'Hat',
            ] as $id => $name
        ) {
            $this->connection->insert(
                table: 'pivot_products',
            )
                ->set(column: 'id', value: $id)
                ->set(column: 'name', value: $name)
                ->execute();
        }

        foreach (
            [
                10 => 'Men',
                11 => 'Sale',
                12 => 'New',
            ] as $id => $name
        ) {
            $this->connection->insert(
                table: 'pivot_categories',
            )
                ->set(column: 'id', value: $id)
                ->set(column: 'name', value: $name)
                ->execute();
        }

        $this->seedCategoryProduct(
            productId: 1,
            categoryId: 10,
            sort: 1,
            priority: 2,
            note: 'main',
        );

        $this->seedCategoryProduct(
            productId: 1,
            categoryId: 11,
            sort: 2,
            priority: 1,
            note: null,
        );

        $this->seedCategoryProduct(
            productId: 2,
            categoryId: 12,
            sort: 5,
            priority: 3,
            note: null,
        );

        $this->connection->insert(
            table: 'pivot_labelables',
        )
            ->set(column: 'labelable_type', value: PivotProduct::class)
            ->set(column: 'labelable_id', value: 1)
            ->set(column: 'category_id', value: 12)
            ->set(column: 'weight', value: 5)
            ->execute();
    }

    private function seedCategoryProduct(
        int $productId,
        int $categoryId,
        int $sort,
        int $priority,
        ?string $note,
    ): void {
        $this->connection->insert(
            table: 'pivot_category_product',
        )
            ->set(column: 'product_id', value: $productId)
            ->set(column: 'category_id', value: $categoryId)
            ->set(column: 'sort', value: $sort)
            ->set(column: 'priority', value: $priority)
            ->set(column: 'note', value: $note)
            ->execute();
    }

    /**
     * @return Relation<PivotCategory>
     */
    private function categoriesOf(
        int $productId,
    ): Relation {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: $productId,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        return $product->categories;
    }

    /**
     * @return Relation<PivotCategory>
     */
    private function labelsOf(
        int $productId,
    ): Relation {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: $productId,
        );

        self::assertInstanceOf(Relation::class, $product->labels);

        return $product->labels;
    }

    /**
     * @param iterable<PivotCategory> $categories
     * @return list<string>
     */
    private function names(
        iterable $categories,
    ): array {
        $names = [];

        foreach ($categories as $category) {
            $names[] = $category->name;
        }

        \sort($names);

        return $names;
    }

    private function categoryPivot(
        int $productId,
        int $categoryId,
    ): PivotCategoryProduct {
        $pivot = $this->categoriesOf($productId)->pivotFor($categoryId);

        self::assertInstanceOf(PivotCategoryProduct::class, $pivot);

        return $pivot;
    }

    private function countLinks(
        int $productId,
    ): int {
        return $this->connection->count(
            table: 'pivot_category_product',
        )
            ->where(column: 'product_id', value: $productId)
            ->count();
    }

    public function testPivotForReadsPivotModelLazily(): void
    {
        $categories = $this->categoriesOf(1);
        $men = $categories->pivotFor(10);

        self::assertInstanceOf(PivotCategoryProduct::class, $men);
        self::assertSame(1, $men->sort);
        self::assertSame(Priority::MEDIUM, $men->priority);
        self::assertSame('main', $men->note);

        $sale = $this->modelsManager->fetchById(
            class: PivotCategory::class,
            id: 11,
        );

        $salePivot = $categories->pivotFor($sale);

        self::assertInstanceOf(PivotCategoryProduct::class, $salePivot);
        self::assertSame(2, $salePivot->sort);
        self::assertNull($salePivot->note);
        self::assertNull($categories->pivotFor(12));
    }

    public function testPivotForUsesEagerLoadedPivots(): void
    {
        /** @var list<PivotProduct> $products */
        $products = \iterator_to_array($this->modelsManager->findAll(
            class: PivotProduct::class,
            with: [
                'categories' => null,
            ],
        ), preserve_keys: false);

        $sorts = [];

        foreach ($products as $product) {
            self::assertInstanceOf(Relation::class, $product->categories);
            self::assertNotNull($product->categories->pivot);
            self::assertTrue($product->categories->pivot->isLoaded());

            foreach ($product->categories as $category) {
                $pivot = $product->categories->pivotFor($category);

                self::assertInstanceOf(PivotCategoryProduct::class, $pivot);

                $sorts[$product->name . '/' . $category->name] = $pivot->sort;
            }
        }

        \ksort($sorts);

        self::assertSame(
            [
                'Hat/New' => 5,
                'Shirt/Men' => 1,
                'Shirt/Sale' => 2,
            ],
            $sorts,
        );
    }

    public function testAddByKeyShowsTargetBeforeSaveAndInsertsPivotValues(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $product->categories->add(
            item: 12,
            pivot: [
                'sort' => 3,
                'priority' => Priority::HIGH,
                'note' => 'fresh',
            ],
        );

        self::assertSame(
            [
                'Men',
                'New',
                'Sale',
            ],
            $this->names($product->categories),
        );

        (void) $this->modelsManager->save($product);

        $pivot = $this->categoryPivot(1, 12);

        self::assertSame(3, $pivot->sort);
        self::assertSame(Priority::HIGH, $pivot->priority);
        self::assertSame('fresh', $pivot->note);
        self::assertSame(3, $this->countLinks(1));
    }

    public function testAddOfLinkedTargetUpdatesOnlyGivenPivotValues(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $product->categories->add(
            item: 10,
            pivot: [
                'sort' => 9,
            ],
        );

        $product->categories->add(11);

        (void) $this->modelsManager->save($product);

        $pivot = $this->categoryPivot(1, 10);

        self::assertSame(9, $pivot->sort);
        self::assertSame('main', $pivot->note);
        self::assertSame(2, $this->countLinks(1));
    }

    public function testRemoveByKeyDeletesPivotRow(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $product->categories->remove(11);

        self::assertSame(
            [
                'Men',
            ],
            $this->names($product->categories),
        );

        (void) $this->modelsManager->save($product);

        self::assertSame(
            [
                'Men',
            ],
            $this->names($this->categoriesOf(1)),
        );

        self::assertSame(1, $this->countLinks(2));
    }

    public function testSyncReplacesSetAndKeepsPivotValuesOfKeptRows(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $product->categories->sync(
            [
                11,
                12,
            ],
        );

        self::assertSame(
            [
                'New',
                'Sale',
            ],
            $this->names($product->categories),
        );

        (void) $this->modelsManager->save($product);

        self::assertSame(
            [
                'New',
                'Sale',
            ],
            $this->names($this->categoriesOf(1)),
        );

        self::assertSame(2, $this->categoryPivot(1, 11)->sort);
        self::assertSame(0, $this->categoryPivot(1, 12)->sort);
        self::assertSame(1, $this->countLinks(2));
    }

    public function testSyncCombinesWithLaterAddAndRemove(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $product->categories->sync(
            [
                10,
                11,
            ],
        );

        $product->categories->add(
            item: 12,
            pivot: [
                'sort' => 4,
            ],
        );

        $product->categories->remove(11);

        (void) $this->modelsManager->save($product);

        self::assertSame(
            [
                'Men',
                'New',
            ],
            $this->names($this->categoriesOf(1)),
        );

        self::assertSame(4, $this->categoryPivot(1, 12)->sort);
        self::assertFalse($product->categories->hasPendingChanges());
    }

    public function testSyncToEmptyDeletesAllRows(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $product->categories->sync([]);

        self::assertSame(0, $product->categories->count());

        (void) $this->modelsManager->save($product);

        self::assertSame(0, $this->countLinks(1));
    }

    public function testLastOperationWinsOnTheSameTarget(): void
    {
        $categories = $this->categoriesOf(1);

        $categories->add(12);
        $categories->remove(12);

        self::assertSame([], $categories->pendingAdds);
        self::assertSame(1, \sizeof($categories->pendingRemoves));

        $categories->remove(10);
        $categories->add(10);

        self::assertSame(1, \sizeof($categories->pendingAdds));
        self::assertSame(1, \sizeof($categories->pendingRemoves));
    }

    public function testModelAndKeyOfTheSameTargetShareIdentity(): void
    {
        $categories = $this->categoriesOf(1);
        $men = $this->modelsManager->fetchById(
            class: PivotCategory::class,
            id: 10,
        );

        $categories->remove($men);
        $categories->add(10);

        self::assertSame([], $categories->pendingRemoves);
        self::assertSame(1, \sizeof($categories->pendingAdds));
    }

    public function testChangedPivotModelIsSavedWithParent(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->categories);

        $pivot = $product->categories->pivotFor(10);

        self::assertInstanceOf(PivotCategoryProduct::class, $pivot);

        $pivot->sort = 7;
        $pivot->priority = Priority::LOW;

        (void) $this->modelsManager->save($product);

        $reloaded = $this->categoryPivot(1, 10);

        self::assertSame(7, $reloaded->sort);
        self::assertSame(Priority::LOW, $reloaded->priority);
    }

    public function testUnknownPivotPropertyIsRejected(): void
    {
        $categories = $this->categoriesOf(1);

        $properties = [
            'missing',
            'productId',
            'categoryId',
        ];

        foreach ($properties as $property) {
            try {
                $categories->add(
                    item: 12,
                    pivot: [
                        $property => 1,
                    ],
                );

                self::fail('Expected ModelException for pivot value ' . $property);
            } catch (ModelException $exception) {
                self::assertStringContainsString(
                    'Pivot value "' . $property . '"',
                    $exception->getMessage(),
                );
            }
        }

        self::assertSame([], $categories->pendingAdds);
    }

    public function testInvalidKeysAreRejected(): void
    {
        $categories = $this->categoriesOf(1);

        try {
            $categories->add(
                [
                    'id' => 12,
                ],
            );

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'expected a model or a single key value',
                $exception->getMessage(),
            );
        }

        self::assertNotNull($categories->pivot);

        try {
            $categories->pivot->keyOf(new PivotProduct());

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'expected an instance of "' . PivotCategory::class . '"',
                $exception->getMessage(),
            );
        }
    }

    public function testUnsavedTargetHasNoPivotAndNothingToRemove(): void
    {
        $categories = $this->categoriesOf(1);
        $unsaved = new PivotCategory();

        self::assertNull($categories->pivotFor($unsaved));

        $categories->remove($unsaved);

        self::assertSame([], $categories->pendingRemoves);
        self::assertFalse($categories->hasPendingChanges());
    }

    public function testMissingTargetKeyIsDroppedFromIteration(): void
    {
        $categories = $this->categoriesOf(1);

        $categories->add(999);

        self::assertSame(
            [
                'Men',
                'Sale',
            ],
            $this->names($categories),
        );
    }

    public function testMorphToManyPivotReadAndWrite(): void
    {
        $product = $this->modelsManager->fetchById(
            class: PivotProduct::class,
            id: 1,
        );

        self::assertInstanceOf(Relation::class, $product->labels);

        $existing = $product->labels->pivotFor(12);

        self::assertInstanceOf(PivotLabelable::class, $existing);
        self::assertSame(5, $existing->weight);
        self::assertSame(PivotProduct::class, $existing->labelableType);

        $product->labels->sync(
            [
                10,
            ],
        );

        $product->labels->add(
            item: 10,
            pivot: [
                'weight' => 2,
            ],
        );

        (void) $this->modelsManager->save($product);

        $labels = $this->labelsOf(1);

        self::assertSame(
            [
                'Men',
            ],
            $this->names($labels),
        );

        $pivot = $labels->pivotFor(10);

        self::assertInstanceOf(PivotLabelable::class, $pivot);
        self::assertNotNull($pivot->id);
        self::assertSame(2, $pivot->weight);
        self::assertSame(PivotProduct::class, $pivot->labelableType);
        self::assertSame(1, $pivot->labelableId);
    }

    public function testMorphToManyEagerLoadsPivots(): void
    {
        /** @var list<PivotProduct> $products */
        $products = \iterator_to_array($this->modelsManager->findAll(
            class: PivotProduct::class,
            with: [
                'labels' => null,
            ],
        ), preserve_keys: false);

        $weights = [];

        foreach ($products as $product) {
            self::assertInstanceOf(Relation::class, $product->labels);

            foreach ($product->labels as $label) {
                $pivot = $product->labels->pivotFor($label);

                self::assertInstanceOf(PivotLabelable::class, $pivot);

                $weights[$product->name . '/' . $label->name] = $pivot->weight;
            }
        }

        self::assertSame(
            [
                'Shirt/New' => 5,
            ],
            $weights,
        );
    }
}
