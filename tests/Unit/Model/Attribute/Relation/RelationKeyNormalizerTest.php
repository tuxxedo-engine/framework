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

namespace Unit\Model\Attribute\Relation;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Relation\RelationKeyNormalizer;
use Tuxxedo\Model\ModelException;

class RelationKeyNormalizerTest extends TestCase
{
    public function testToColumnsWrapsStringInList(): void
    {
        self::assertSame(
            [
                'user_id',
            ],
            RelationKeyNormalizer::toColumns(value: 'user_id'),
        );
    }

    public function testToColumnsReindexesArrayInput(): void
    {
        self::assertSame(
            [
                'scope',
                'name',
            ],
            RelationKeyNormalizer::toColumns(value: [
                7 => 'scope',
                9 => 'name',
            ]),
        );
    }

    public function testToParentOrderedColumnsAcceptsStringForSingleColumnParent(): void
    {
        self::assertSame(
            [
                'user_id',
            ],
            RelationKeyNormalizer::toParentOrderedColumns(
                value: 'user_id',
                parentPkColumns: [
                    'id',
                ],
                modelClass: \stdClass::class,
                property: 'y',
                keyKind: 'foreignKey',
            ),
        );
    }

    public function testToParentOrderedColumnsRejectsStringForCompositeParent(): void
    {
        try {
            RelationKeyNormalizer::toParentOrderedColumns(
                value: 'user_id',
                parentPkColumns: [
                    'scope',
                    'name',
                ],
                modelClass: \stdClass::class,
                property: 'children',
                keyKind: 'foreignKey',
            );

            self::fail('Expected arity mismatch');
        } catch (ModelException $exception) {
            self::assertStringContainsString('arities must match', $exception->getMessage());
        }
    }

    public function testToParentOrderedColumnsAcceptsPositionalListMatchingArity(): void
    {
        self::assertSame(
            [
                'owner_scope',
                'owner_name',
            ],
            RelationKeyNormalizer::toParentOrderedColumns(
                value: [
                    'owner_scope',
                    'owner_name',
                ],
                parentPkColumns: [
                    'scope',
                    'name',
                ],
                modelClass: \stdClass::class,
                property: 'y',
                keyKind: 'foreignKey',
            ),
        );
    }

    public function testToParentOrderedColumnsRejectsListWithArityMismatch(): void
    {
        try {
            RelationKeyNormalizer::toParentOrderedColumns(
                value: [
                    'owner_scope',
                ],
                parentPkColumns: [
                    'scope',
                    'name',
                ],
                modelClass: \stdClass::class,
                property: 'y',
                keyKind: 'foreignKey',
            );

            self::fail('Expected arity mismatch');
        } catch (ModelException $exception) {
            self::assertStringContainsString('arities must match', $exception->getMessage());
        }
    }

    public function testToParentOrderedColumnsAcceptsMapAndReordersByParentPk(): void
    {
        self::assertSame(
            [
                'owner_scope',
                'owner_name',
            ],
            RelationKeyNormalizer::toParentOrderedColumns(
                value: [
                    'name' => 'owner_name',
                    'scope' => 'owner_scope',
                ],
                parentPkColumns: [
                    'scope',
                    'name',
                ],
                modelClass: \stdClass::class,
                property: 'y',
                keyKind: 'foreignKey',
            ),
        );
    }

    public function testToParentOrderedColumnsRejectsMapMissingParentColumn(): void
    {
        try {
            RelationKeyNormalizer::toParentOrderedColumns(
                value: [
                    'scope' => 'owner_scope',
                    'other' => 'owner_other',
                ],
                parentPkColumns: [
                    'scope',
                    'name',
                ],
                modelClass: \stdClass::class,
                property: 'y',
                keyKind: 'foreignKey',
            );

            self::fail('Expected missing parent column');
        } catch (ModelException $exception) {
            self::assertStringContainsString('map-form foreignKey but does not provide an entry for parent column', $exception->getMessage());
        }
    }

    public function testToParentOrderedColumnsOrNullShortCircuitsOnNull(): void
    {
        self::assertNull(RelationKeyNormalizer::toParentOrderedColumnsOrNull(
            value: null,
            parentPkColumns: [
                'scope',
                'name',
            ],
            modelClass: \stdClass::class,
            property: 'y',
            keyKind: 'foreignKey',
        ));
    }

    public function testToParentOrderedColumnsOrNullDelegatesWhenValueGiven(): void
    {
        self::assertSame(
            [
                'owner_scope',
                'owner_name',
            ],
            RelationKeyNormalizer::toParentOrderedColumnsOrNull(
                value: [
                    'owner_scope',
                    'owner_name',
                ],
                parentPkColumns: [
                    'scope',
                    'name',
                ],
                modelClass: \stdClass::class,
                property: 'y',
                keyKind: 'foreignKey',
            ),
        );
    }
}
