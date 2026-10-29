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

use Fixture\Model\EnumKeyedRecord;
use Fixture\Model\PostStatus;

abstract class AbstractEnumPrimaryKeyIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createEnumKeyedRecordsTable();
    }

    public function testEnumPrimaryKeyRoundTripsThroughInsertAndFetch(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::PUBLISHED;
        $record->label = 'live';

        (void) $this->modelsManager->save($record);

        $refetched = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::PUBLISHED,
        );

        self::assertSame(PostStatus::PUBLISHED, $refetched->status);
        self::assertSame('live', $refetched->label);
    }

    public function testEnumPrimaryKeyUpdatesMatchingRowOnly(): void
    {
        $draft = new EnumKeyedRecord();
        $draft->status = PostStatus::DRAFT;
        $draft->label = 'draft';

        $published = new EnumKeyedRecord();
        $published->status = PostStatus::PUBLISHED;
        $published->label = 'published';

        $savedDraft = $this->modelsManager->save($draft);

        (void) $this->modelsManager->save($published);

        $savedDraft->label = 'edited';

        (void) $this->modelsManager->save($savedDraft);

        self::assertSame(
            'edited',
            $this->modelsManager->fetchById(
                class: EnumKeyedRecord::class,
                id: PostStatus::DRAFT,
            )->label,
        );

        self::assertSame(
            'published',
            $this->modelsManager->fetchById(
                class: EnumKeyedRecord::class,
                id: PostStatus::PUBLISHED,
            )->label,
        );
    }

    public function testEnumPrimaryKeyRefreshReloadsRow(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::ARCHIVED;
        $record->label = 'before';

        $saved = $this->modelsManager->save($record);

        $this->connection->update(
            table: 'enum_keyed_records',
        )
            ->set(column: 'label', value: 'after')
            ->where(column: 'status', value: PostStatus::ARCHIVED->value)
            ->execute();

        $refreshed = $this->modelsManager->refresh($saved);

        self::assertSame(PostStatus::ARCHIVED, $refreshed->status);
        self::assertSame('after', $refreshed->label);
    }

    public function testEnumPrimaryKeyExistsById(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::PUBLISHED;
        $record->label = 'exists';

        (void) $this->modelsManager->save($record);

        self::assertTrue(
            $this->modelsManager->existsById(
                class: EnumKeyedRecord::class,
                id: PostStatus::PUBLISHED,
            ),
        );

        self::assertFalse(
            $this->modelsManager->existsById(
                class: EnumKeyedRecord::class,
                id: PostStatus::ARCHIVED,
            ),
        );
    }

    public function testEnumPrimaryKeyDeleteRemovesRow(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::DRAFT;
        $record->label = 'gone';

        $saved = $this->modelsManager->save($record);

        self::assertTrue($this->modelsManager->delete($saved));

        self::assertNull(
            $this->modelsManager->findById(
                class: EnumKeyedRecord::class,
                id: PostStatus::DRAFT,
            ),
        );
    }
}
