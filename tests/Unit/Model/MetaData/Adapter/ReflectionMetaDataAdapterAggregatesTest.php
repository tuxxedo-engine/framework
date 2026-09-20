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

namespace Unit\Model\MetaData\Adapter;

use Fixture\Model\Broken\AggregateCountUnexpectedColumn;
use Fixture\Model\Broken\AggregateDuplicateAlias;
use Fixture\Model\Broken\AggregateNonNullableSlot;
use Fixture\Model\Broken\AggregateNonNumericSlot;
use Fixture\Model\Broken\AggregateOnMorphTo;
use Fixture\Model\Broken\AggregateSumMissingColumn;
use Fixture\Model\Broken\AggregateUnknownRelation;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\ModelException;

class ReflectionMetaDataAdapterAggregatesTest extends TestCase
{
    private ReflectionMetaDataAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new ReflectionMetaDataAdapter();
    }

    /**
     * @param class-string $modelClass
     */
    private function assertRejectsModelWithMessage(
        string $modelClass,
        string $needle,
    ): void {
        try {
            $this->adapter->getModel($modelClass);

            self::fail('Expected ModelException was not thrown for ' . $modelClass);
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                $needle,
                $exception->getMessage(),
            );
        }
    }

    public function testRejectsAggregateReferencingUnknownRelation(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateUnknownRelation::class,
            needle: 'not declared on the model',
        );
    }

    public function testRejectsAggregateOnMorphToRelation(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateOnMorphTo::class,
            needle: 'MorphTo aggregates are not supported',
        );
    }

    public function testRejectsSumAggregateWithoutColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateSumMissingColumn::class,
            needle: 'requires a target column',
        );
    }

    public function testRejectsCountAggregateWithColumn(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateCountUnexpectedColumn::class,
            needle: 'does not accept a target column',
        );
    }

    public function testRejectsNonNullableAggregateSlot(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateNonNullableSlot::class,
            needle: 'must be nullable',
        );
    }

    public function testRejectsNonNumericAggregateSlot(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateNonNumericSlot::class,
            needle: 'must be a nullable int|float property',
        );
    }

    public function testRejectsDuplicateAggregateAlias(): void
    {
        $this->assertRejectsModelWithMessage(
            modelClass: AggregateDuplicateAlias::class,
            needle: 'collides with existing aggregate',
        );
    }
}
