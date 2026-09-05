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

namespace Unit\Model\Aggregate;

use Fixture\Model\Aggregate\AggregateFailingModel;
use Fixture\Model\Polymorphic\Article;
use Fixture\Validator\FixtureViolationCode;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Model\Aggregate\AggregateValidator;
use Tuxxedo\Model\Aggregate\CollectedEntity;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;

class AggregateValidatorTest extends TestCase
{
    private AggregateValidator $aggregateValidator;

    private MetaData $metaData;

    protected function setUp(): void
    {
        $this->metaData = new MetaData(
            adapter: new ReflectionMetaDataAdapter(),
        );

        $this->aggregateValidator = new AggregateValidator(
            validator: new Validator(
                container: new Container(),
            ),
        );
    }

    public function testValidateOrThrowReturnsSilentlyWhenAllEntitiesPass(): void
    {
        $article = new Article();
        $collected = [
            new CollectedEntity(
                path: '',
                entity: $article,
                metaData: $this->metaData->getModel(Article::class),
            ),
        ];

        $this->expectNotToPerformAssertions();

        $this->aggregateValidator->validateOrThrow($collected);
    }

    public function testValidateOrThrowRaisesForRootFailureAndKeepsPropertyPathAsIs(): void
    {
        $failing = new AggregateFailingModel();
        $collected = [
            new CollectedEntity(
                path: '',
                entity: $failing,
                metaData: $this->metaData->getModel(AggregateFailingModel::class),
            ),
        ];

        try {
            $this->aggregateValidator->validateOrThrow($collected);

            self::fail('Expected ValidationException from root failure');
        } catch (ValidationException $exception) {
            self::assertCount(1, $exception->violations);
            self::assertSame('name', $exception->violations[0]->propertyPath);
            self::assertSame(FixtureViolationCode::ALWAYS_FAIL, $exception->violations[0]->code);
        }
    }

    public function testValidateOrThrowPrefixesChildViolationWithCollectedPath(): void
    {
        $child = new AggregateFailingModel();
        $collected = [
            new CollectedEntity(
                path: 'comments.0',
                entity: $child,
                metaData: $this->metaData->getModel(AggregateFailingModel::class),
            ),
        ];

        try {
            $this->aggregateValidator->validateOrThrow($collected);

            self::fail('Expected ValidationException from child failure');
        } catch (ValidationException $exception) {
            self::assertCount(1, $exception->violations);
            self::assertSame('comments.0.name', $exception->violations[0]->propertyPath);
        }
    }

    public function testValidateOrThrowMergesMultipleFailuresIntoOneException(): void
    {
        $root = new AggregateFailingModel();
        $child = new AggregateFailingModel();
        $grandchild = new AggregateFailingModel();

        $collected = [
            new CollectedEntity(
                path: '',
                entity: $root,
                metaData: $this->metaData->getModel(AggregateFailingModel::class),
            ),
            new CollectedEntity(
                path: 'children.0',
                entity: $child,
                metaData: $this->metaData->getModel(AggregateFailingModel::class),
            ),
            new CollectedEntity(
                path: 'children.0.children.2',
                entity: $grandchild,
                metaData: $this->metaData->getModel(AggregateFailingModel::class),
            ),
        ];

        try {
            $this->aggregateValidator->validateOrThrow($collected);

            self::fail('Expected ValidationException from aggregated failures');
        } catch (ValidationException $exception) {
            $paths = \array_map(
                static fn ($v): string => $v->propertyPath,
                $exception->violations,
            );

            self::assertSame(
                [
                    'name',
                    'children.0.name',
                    'children.0.children.2.name',
                ],
                $paths,
            );
        }
    }

    public function testValidateOrThrowMixesPassingAndFailingEntitiesReportingOnlyFailures(): void
    {
        $collected = [
            new CollectedEntity(
                path: '',
                entity: new Article(),
                metaData: $this->metaData->getModel(Article::class),
            ),
            new CollectedEntity(
                path: 'items.0',
                entity: new AggregateFailingModel(),
                metaData: $this->metaData->getModel(AggregateFailingModel::class),
            ),
        ];

        try {
            $this->aggregateValidator->validateOrThrow($collected);

            self::fail('Expected ValidationException from failing item');
        } catch (ValidationException $exception) {
            self::assertCount(1, $exception->violations);
            self::assertSame('items.0.name', $exception->violations[0]->propertyPath);
        }
    }
}
