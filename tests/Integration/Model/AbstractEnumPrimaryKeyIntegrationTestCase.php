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

use Fixture\Model\EnumKeyedAttachment;
use Fixture\Model\EnumKeyedComment;
use Fixture\Model\EnumKeyedEntry;
use Fixture\Model\EnumKeyedNote;
use Fixture\Model\EnumKeyedRecord;
use Fixture\Model\EnumKeyedRecordTag;
use Fixture\Model\EnumKeyedTag;
use Fixture\Model\PostStatus;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Model\Relation;
use Tuxxedo\Model\ValidationScope;

abstract class AbstractEnumPrimaryKeyIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createEnumKeyedRecordsTable();
        $this->createEnumKeyedEntriesTable();
        $this->createEnumKeyedNotesTable();
        $this->createEnumKeyedCommentsTable();
        $this->createEnumKeyedTagsTable();
        $this->createEnumKeyedRecordTagTable();
        $this->createEnumKeyedAttachmentsTable();
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

    public function testHasManyLoadsChildrenOfEnumKeyedParentLazily(): void
    {
        $this->seedRelations();

        $draft = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::DRAFT,
        );

        self::assertNotNull($draft->entries);
        self::assertSame(
            [
                'draft one',
                'draft two',
            ],
            $this->titles($draft->entries->orderBy('title')),
        );
    }

    public function testHasManyEagerLoadsChildrenByEnumKey(): void
    {
        $this->seedRelations();

        $counts = [];

        $records = $this->modelsManager->query(EnumKeyedRecord::class)->with(
            [
                'entries' => null,
            ],
        );

        foreach ($records as $record) {
            self::assertNotNull($record->entries);

            $counts[$record->status->value] = \sizeof($this->titles($record->entries));
        }

        \ksort($counts);

        self::assertSame(
            [
                'draft' => 2,
                'published' => 1,
            ],
            $counts,
        );
    }

    public function testBelongsToLoadsEnumKeyedOwnerLazilyAndEagerly(): void
    {
        $this->seedRelations();

        $lazy = $this->modelsManager->query(EnumKeyedEntry::class)
            ->where('title', 'published one')
            ->first();

        self::assertNotNull($lazy);
        self::assertSame('published', $lazy->record?->label);

        $labels = [];

        $entries = $this->modelsManager->query(EnumKeyedEntry::class)
            ->with(
                [
                    'record' => null,
                ],
            )
            ->orderBy('title');

        foreach ($entries as $entry) {
            $labels[$entry->title] = $entry->record?->label;
        }

        self::assertSame(
            [
                'draft one' => 'draft',
                'draft two' => 'draft',
                'published one' => 'published',
            ],
            $labels,
        );
    }

    public function testHasOneFromEnumKeyToStringForeignKey(): void
    {
        $this->seedRelations();

        $published = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::PUBLISHED,
        );

        self::assertSame('published note', $published->note?->body);

        $bodies = [];

        $records = $this->modelsManager->query(EnumKeyedRecord::class)->with(
            [
                'note' => null,
            ],
        );

        foreach ($records as $record) {
            $bodies[$record->status->value] = $record->note?->body;
        }

        \ksort($bodies);

        self::assertSame(
            [
                'draft' => null,
                'published' => 'published note',
            ],
            $bodies,
        );
    }

    public function testBelongsToFromStringForeignKeyToEnumKeyedOwner(): void
    {
        $this->seedRelations();

        $note = $this->modelsManager->query(EnumKeyedNote::class)->first();

        self::assertNotNull($note);
        self::assertSame(PostStatus::PUBLISHED, $note->record?->status);
    }

    public function testAggregateSaveWritesEnumKeyIntoChildForeignKeys(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::ARCHIVED;
        $record->label = 'archived';
        $record->entries = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: EnumKeyedEntry::class,
        );

        $entry = new EnumKeyedEntry();
        $entry->title = 'archived one';
        $record->entries->add($entry);

        $note = new EnumKeyedNote();
        $note->body = 'archived note';
        $record->note = $note;

        (void) $this->modelsManager->save(
            model: $record,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertSame(PostStatus::ARCHIVED, $entry->status);
        self::assertSame('archived', $note->status);
        self::assertNotNull($entry->id);
        self::assertNotNull($note->id);
    }

    public function testAggregateSaveWritesOwnerKeyIntoEnumForeignKey(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::PUBLISHED;
        $record->label = 'published';

        $note = new EnumKeyedNote();
        $note->status = 'archived';
        $note->body = 'archived note';

        (void) $this->modelsManager->save($note);

        $fromRecord = new EnumKeyedEntry();
        $fromRecord->title = 'from record';
        $fromRecord->record = $record;

        (void) $this->modelsManager->save(
            model: $fromRecord,
            scope: ValidationScope::AGGREGATE,
        );

        $fromNote = new EnumKeyedEntry();
        $fromNote->title = 'from note';
        $fromNote->note = $note;

        (void) $this->modelsManager->save(
            model: $fromNote,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertSame(PostStatus::PUBLISHED, $fromRecord->status);
        self::assertSame(PostStatus::ARCHIVED, $fromNote->status);
    }

    public function testHasManyThroughByEnumKey(): void
    {
        $this->seedExtendedRelations();

        $draft = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::DRAFT,
        );

        self::assertNotNull($draft->comments);
        self::assertSame(
            [
                'first draft comment',
                'second draft comment',
            ],
            $this->commentBodies($draft->comments->orderBy('body')),
        );

        self::assertSame(
            [
                'draft' => 2,
                'published' => 1,
            ],
            $this->eagerCounts(
                relation: 'comments',
                count: static fn (EnumKeyedRecord $record): int => $record->comments === null
                    ? 0
                    : $record->comments->count(),
            ),
        );
    }

    public function testHasOneThroughByEnumKey(): void
    {
        $this->seedExtendedRelations();

        $published = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::PUBLISHED,
        );

        self::assertSame('published comment', $published->firstComment?->body);

        $records = $this->modelsManager->query(EnumKeyedRecord::class)->with(
            [
                'firstComment' => null,
            ],
        );

        $bodies = [];

        foreach ($records as $record) {
            $bodies[$record->status->value] = $record->firstComment === null
                ? null
                : $record->firstComment->body;
        }

        self::assertSame('published comment', $bodies['published'] ?? null);
        self::assertNotNull($bodies['draft'] ?? null);
    }

    public function testBelongsToManyByEnumKey(): void
    {
        $this->seedExtendedRelations();

        $draft = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::DRAFT,
        );

        self::assertNotNull($draft->tags);
        self::assertSame(
            [
                'blue',
                'red',
            ],
            $this->tagNames($draft->tags->orderBy('name')),
        );

        self::assertSame(
            [
                'draft' => 2,
                'published' => 1,
            ],
            $this->eagerCounts(
                relation: 'tags',
                count: static fn (EnumKeyedRecord $record): int => $record->tags === null
                    ? 0
                    : $record->tags->count(),
            ),
        );
    }

    public function testBelongsToManyWritesEnumKeysThroughPivotModel(): void
    {
        $this->seedExtendedRelations();

        $tag = $this->modelsManager->findFirst(
            class: EnumKeyedTag::class,
            criteria: static function (SelectStatementInterface $statement): void {
                $statement->where('name', 'red');
            },
        );

        self::assertNotNull($tag);
        self::assertInstanceOf(Relation::class, $tag->records);

        $tag->records->sync(
            [
                PostStatus::PUBLISHED,
            ],
        );

        $statuses = [];

        foreach ($tag->records as $record) {
            $statuses[] = $record->status->value;
        }

        \sort($statuses);

        self::assertSame(
            [
                'published',
            ],
            $statuses,
        );

        (void) $this->modelsManager->save($tag);

        $refetched = $this->modelsManager->fetchById(
            class: EnumKeyedTag::class,
            id: $tag->id ?? 0,
        );

        self::assertInstanceOf(Relation::class, $refetched->records);

        $pivot = $refetched->records->pivotFor(PostStatus::PUBLISHED);

        self::assertInstanceOf(EnumKeyedRecordTag::class, $pivot);
        self::assertSame(PostStatus::PUBLISHED, $pivot->recordStatus);
        self::assertNull($refetched->records->pivotFor(PostStatus::DRAFT));
    }

    public function testMorphManyAndMorphOneByEnumKey(): void
    {
        $this->seedExtendedRelations();

        $draft = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::DRAFT,
        );

        self::assertNotNull($draft->attachments);
        self::assertSame(
            [
                'a.pdf',
                'b.pdf',
            ],
            $this->attachmentNames($draft->attachments->orderBy('name')),
        );

        $published = $this->modelsManager->fetchById(
            class: EnumKeyedRecord::class,
            id: PostStatus::PUBLISHED,
        );

        self::assertSame('c.pdf', $published->attachment?->name);

        self::assertSame(
            [
                'draft' => 2,
                'published' => 1,
            ],
            $this->eagerCounts(
                relation: 'attachments',
                count: static fn (EnumKeyedRecord $record): int => $record->attachments === null
                    ? 0
                    : $record->attachments->count(),
            ),
        );
    }

    public function testMorphToResolvesEnumKeyedHost(): void
    {
        $this->seedExtendedRelations();

        $attachment = $this->modelsManager->query(EnumKeyedAttachment::class)
            ->where('name', 'c.pdf')
            ->first();

        self::assertNotNull($attachment);
        self::assertInstanceOf(EnumKeyedRecord::class, $attachment->host);
        self::assertSame(PostStatus::PUBLISHED, $attachment->host->status);

        $attachments = $this->modelsManager->query(EnumKeyedAttachment::class)
            ->with(
                [
                    'host' => null,
                ],
            )
            ->orderBy('name');

        $hosts = [];

        foreach ($attachments as $eager) {
            self::assertInstanceOf(EnumKeyedRecord::class, $eager->host);

            $hosts[$eager->name] = $eager->host->status;
        }

        self::assertSame(
            [
                'a.pdf' => PostStatus::DRAFT,
                'b.pdf' => PostStatus::DRAFT,
                'c.pdf' => PostStatus::PUBLISHED,
            ],
            $hosts,
        );
    }

    public function testAggregateSaveWritesEnumKeyIntoPivotAndMorphChildren(): void
    {
        $record = new EnumKeyedRecord();
        $record->status = PostStatus::ARCHIVED;
        $record->label = 'archived';
        $record->tags = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: EnumKeyedTag::class,
        );
        $record->attachments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: EnumKeyedAttachment::class,
        );

        $tag = new EnumKeyedTag();
        $tag->name = 'green';
        $record->tags->add($tag);

        $attachment = new EnumKeyedAttachment();
        $attachment->name = 'd.pdf';
        $record->attachments->add($attachment);

        (void) $this->modelsManager->save(
            model: $record,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertSame(EnumKeyedRecord::class, $attachment->hostType);
        self::assertSame('archived', $attachment->hostId);
        self::assertSame(
            1,
            $this->connection->count(
                table: 'enum_keyed_record_tag',
            )
                ->where('record_status', 'archived')
                ->count(),
        );
    }

    private function seedRelations(): void
    {
        foreach ([PostStatus::DRAFT, PostStatus::PUBLISHED] as $status) {
            $record = new EnumKeyedRecord();
            $record->status = $status;
            $record->label = $status->value;

            (void) $this->modelsManager->save($record);
        }

        $entries = [
            'draft one' => PostStatus::DRAFT,
            'draft two' => PostStatus::DRAFT,
            'published one' => PostStatus::PUBLISHED,
        ];

        foreach ($entries as $title => $status) {
            $entry = new EnumKeyedEntry();
            $entry->status = $status;
            $entry->title = $title;

            (void) $this->modelsManager->save($entry);
        }

        $note = new EnumKeyedNote();
        $note->status = PostStatus::PUBLISHED->value;
        $note->body = 'published note';

        (void) $this->modelsManager->save($note);
    }

    /**
     * @param iterable<EnumKeyedEntry> $entries
     * @return list<string>
     */
    private function titles(
        iterable $entries,
    ): array {
        $titles = [];

        foreach ($entries as $entry) {
            $titles[] = $entry->title;
        }

        return $titles;
    }

    private function seedExtendedRelations(): void
    {
        $this->seedRelations();

        $entryIds = [];

        foreach ($this->modelsManager->query(EnumKeyedEntry::class) as $entry) {
            $entryIds[$entry->title] = $entry->id ?? 0;
        }

        $comments = [
            'first draft comment' => 'draft one',
            'second draft comment' => 'draft two',
            'published comment' => 'published one',
        ];

        foreach ($comments as $body => $title) {
            $comment = new EnumKeyedComment();
            $comment->entryId = $entryIds[$title] ?? 0;
            $comment->body = $body;

            (void) $this->modelsManager->save($comment);
        }

        $tagIds = [];

        foreach (['red', 'blue'] as $name) {
            $tag = new EnumKeyedTag();
            $tag->name = $name;

            $tagIds[$name] = $this->modelsManager->save($tag)->id ?? 0;
        }

        $pivot = [
            [
                PostStatus::DRAFT,
                'red',
            ],
            [
                PostStatus::DRAFT,
                'blue',
            ],
            [
                PostStatus::PUBLISHED,
                'blue',
            ],
        ];

        foreach ($pivot as [$status, $name]) {
            $this->connection->insert(
                table: 'enum_keyed_record_tag',
            )
                ->set(column: 'record_status', value: $status->value)
                ->set(column: 'tag_id', value: $tagIds[$name])
                ->execute();
        }

        $attachments = [
            'a.pdf' => PostStatus::DRAFT,
            'b.pdf' => PostStatus::DRAFT,
            'c.pdf' => PostStatus::PUBLISHED,
        ];

        foreach ($attachments as $name => $status) {
            $attachment = new EnumKeyedAttachment();
            $attachment->hostType = EnumKeyedRecord::class;
            $attachment->hostId = $status->value;
            $attachment->name = $name;

            (void) $this->modelsManager->save($attachment);
        }
    }

    /**
     * @param \Closure(EnumKeyedRecord): int $count
     * @return array<string, int>
     */
    private function eagerCounts(
        string $relation,
        \Closure $count,
    ): array {
        $records = $this->modelsManager->query(EnumKeyedRecord::class)->with(
            [
                $relation => null,
            ],
        );

        $counts = [];

        foreach ($records as $record) {
            $counts[$record->status->value] = $count($record);
        }

        \ksort($counts);

        return $counts;
    }

    /**
     * @param iterable<EnumKeyedComment> $comments
     * @return list<string>
     */
    private function commentBodies(
        iterable $comments,
    ): array {
        $bodies = [];

        foreach ($comments as $comment) {
            $bodies[] = $comment->body;
        }

        return $bodies;
    }

    /**
     * @param iterable<EnumKeyedTag> $tags
     * @return list<string>
     */
    private function tagNames(
        iterable $tags,
    ): array {
        $names = [];

        foreach ($tags as $tag) {
            $names[] = $tag->name;
        }

        return $names;
    }

    /**
     * @param iterable<EnumKeyedAttachment> $attachments
     * @return list<string>
     */
    private function attachmentNames(
        iterable $attachments,
    ): array {
        $names = [];

        foreach ($attachments as $attachment) {
            $names[] = $attachment->name;
        }

        return $names;
    }
}
