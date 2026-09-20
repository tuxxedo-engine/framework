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

use Fixture\Model\Composite\CompositeChildOfSingleParent;
use Fixture\Model\Composite\CompositeMorphNote;
use Fixture\Model\Composite\CompositeOwner;
use Fixture\Model\Composite\CompositeOwnerHasManyChild;
use Fixture\Model\Composite\CompositeOwnerHasOneChild;
use Fixture\Model\Composite\CompositeTag;
use Fixture\Model\User;
use Tuxxedo\Model\Relation;
use Tuxxedo\Model\ValidationScope;

abstract class AbstractCompositeKeyRelationsIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createCompositeOwnersTable();
        $this->createCompositeOwnerHasOneChildrenTable();
        $this->createCompositeOwnerHasManyChildrenTable();
        $this->createCompositeChildOfSingleParentTable();
        $this->createCompositeTagsTable();
        $this->createCompositeOwnerTagsPivotTable();
        $this->createCompositeMorphNotesTable();
        $this->createCompositeMorphTagsPivotTable();

        $this->seedUser(id: 1, name: 'Alice');

        $this->seedCompositeOwner(
            scope: 'system',
            name: 'alpha',
            label: 'System Alpha',
        );

        $this->seedCompositeOwner(
            scope: 'system',
            name: 'beta',
            label: 'System Beta',
        );

        $this->seedCompositeOwnerHasOneChild(
            id: 100,
            ownerScope: 'system',
            ownerName: 'alpha',
            label: 'single child of alpha',
        );

        $this->seedCompositeOwnerHasManyChild(
            id: 200,
            ownerScope: 'system',
            ownerName: 'alpha',
            label: 'many child one of alpha',
        );

        $this->seedCompositeOwnerHasManyChild(
            id: 201,
            ownerScope: 'system',
            ownerName: 'alpha',
            label: 'many child two of alpha',
        );

        $this->seedCompositeOwnerHasManyChild(
            id: 202,
            ownerScope: 'system',
            ownerName: 'alpha',
            label: 'many child three of alpha',
        );

        $this->seedCompositeChildOfSingleParent(
            scope: 'user',
            name: 'alice.settings',
            userId: 1,
            label: 'alice user settings',
        );

        $this->seedCompositeTag(
            realm: 'color',
            code: 'red',
            label: 'Red',
        );

        $this->seedCompositeTag(
            realm: 'color',
            code: 'blue',
            label: 'Blue',
        );

        $this->seedCompositeTag(
            realm: 'size',
            code: 'large',
            label: 'Large',
        );

        $this->seedCompositeOwnerTagPivot(
            ownerScope: 'system',
            ownerName: 'alpha',
            tagRealm: 'color',
            tagCode: 'red',
        );

        $this->seedCompositeOwnerTagPivot(
            ownerScope: 'system',
            ownerName: 'alpha',
            tagRealm: 'size',
            tagCode: 'large',
        );

        $this->seedCompositeOwnerTagPivot(
            ownerScope: 'system',
            ownerName: 'beta',
            tagRealm: 'color',
            tagCode: 'blue',
        );

        $this->seedCompositeMorphNote(
            id: 500,
            ownerType: CompositeOwner::class,
            ownerScope: 'system',
            ownerName: 'alpha',
            body: 'alpha note one',
        );

        $this->seedCompositeMorphNote(
            id: 501,
            ownerType: CompositeOwner::class,
            ownerScope: 'system',
            ownerName: 'alpha',
            body: 'alpha note two',
        );

        $this->seedCompositeMorphNote(
            id: 502,
            ownerType: CompositeOwner::class,
            ownerScope: 'system',
            ownerName: 'beta',
            body: 'beta note one',
        );

        $this->seedCompositeMorphTagPivot(
            ownerType: CompositeOwner::class,
            ownerScope: 'system',
            ownerName: 'alpha',
            tagRealm: 'color',
            tagCode: 'red',
        );

        $this->seedCompositeMorphTagPivot(
            ownerType: CompositeOwner::class,
            ownerScope: 'system',
            ownerName: 'alpha',
            tagRealm: 'size',
            tagCode: 'large',
        );

        $this->seedCompositeMorphTagPivot(
            ownerType: CompositeOwner::class,
            ownerScope: 'system',
            ownerName: 'beta',
            tagRealm: 'color',
            tagCode: 'blue',
        );
    }

    private function seedUser(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(
            table: 'users',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: \strtolower($name) . '@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->execute();
    }

    private function seedCompositeOwner(
        string $scope,
        string $name,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'composite_owners',
        )
            ->set(column: 'scope', value: $scope)
            ->set(column: 'name', value: $name)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCompositeOwnerHasOneChild(
        int $id,
        string $ownerScope,
        string $ownerName,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'composite_owner_hasone_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'owner_scope', value: $ownerScope)
            ->set(column: 'owner_name', value: $ownerName)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCompositeOwnerHasManyChild(
        int $id,
        string $ownerScope,
        string $ownerName,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'composite_owner_hasmany_children',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'owner_scope', value: $ownerScope)
            ->set(column: 'owner_name', value: $ownerName)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCompositeChildOfSingleParent(
        string $scope,
        string $name,
        int $userId,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'composite_child_of_single_parent',
        )
            ->set(column: 'scope', value: $scope)
            ->set(column: 'name', value: $name)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCompositeTag(
        string $realm,
        string $code,
        string $label,
    ): void {
        $this->connection->insert(
            table: 'composite_tags',
        )
            ->set(column: 'realm', value: $realm)
            ->set(column: 'code', value: $code)
            ->set(column: 'label', value: $label)
            ->execute();
    }

    private function seedCompositeOwnerTagPivot(
        string $ownerScope,
        string $ownerName,
        string $tagRealm,
        string $tagCode,
    ): void {
        $this->connection->insert(
            table: 'composite_owner_tags',
        )
            ->set(column: 'owner_scope', value: $ownerScope)
            ->set(column: 'owner_name', value: $ownerName)
            ->set(column: 'tag_realm', value: $tagRealm)
            ->set(column: 'tag_code', value: $tagCode)
            ->execute();
    }

    /**
     * @param class-string $ownerType
     */
    private function seedCompositeMorphNote(
        int $id,
        string $ownerType,
        string $ownerScope,
        string $ownerName,
        string $body,
    ): void {
        $this->connection->insert(
            table: 'composite_morph_notes',
        )
            ->set(column: 'id', value: $id)
            ->set(column: 'ownerType', value: $ownerType)
            ->set(column: 'ownerScope', value: $ownerScope)
            ->set(column: 'ownerName', value: $ownerName)
            ->set(column: 'body', value: $body)
            ->execute();
    }

    /**
     * @param class-string $ownerType
     */
    private function seedCompositeMorphTagPivot(
        string $ownerType,
        string $ownerScope,
        string $ownerName,
        string $tagRealm,
        string $tagCode,
    ): void {
        $this->connection->insert(
            table: 'composite_morph_tags',
        )
            ->set(column: 'owner_type', value: $ownerType)
            ->set(column: 'owner_scope', value: $ownerScope)
            ->set(column: 'owner_name', value: $ownerName)
            ->set(column: 'tag_realm', value: $tagRealm)
            ->set(column: 'tag_code', value: $tagCode)
            ->execute();
    }

    public function testCompositeOwnerCanBeFetchedByCompositeKey(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(
            CompositeOwner::class,
            $owner,
        );

        self::assertSame('System Alpha', $owner->label);
    }

    public function testCompositeOwnerHasOneRelationLoadsChild(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(
            CompositeOwnerHasOneChild::class,
            $owner->singleChild,
        );

        self::assertSame('single child of alpha', $owner->singleChild->label);
    }

    public function testCompositeOwnerHasManyRelationLoadsChildren(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $owner->manyChildren,
        );

        self::assertSame(
            3,
            $owner->manyChildren->count(),
        );
    }

    public function testCompositeOwnerHasManyRelationIterationYieldsChildren(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $owner->manyChildren,
        );

        $labels = [];

        foreach ($owner->manyChildren as $child) {
            $labels[] = $child->label;
        }

        \sort($labels);

        self::assertSame(
            [
                'many child one of alpha',
                'many child three of alpha',
                'many child two of alpha',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerBetaHasNoChildren(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        self::assertNull($owner->singleChild);

        self::assertInstanceOf(
            Relation::class,
            $owner->manyChildren,
        );

        self::assertSame(
            0,
            $owner->manyChildren->count(),
        );
    }

    public function testBelongsToLoadsCompositeParent(): void
    {
        $child = $this->modelsManager->fetchById(
            class: CompositeOwnerHasManyChild::class,
            id: 200,
        );

        self::assertInstanceOf(
            CompositeOwner::class,
            $child->owner,
        );

        self::assertSame('system', $child->owner->scope);
        self::assertSame('alpha', $child->owner->name);
        self::assertSame('System Alpha', $child->owner->label);
    }

    public function testHasOneChildBelongsToLoadsCompositeParent(): void
    {
        $child = $this->modelsManager->fetchById(
            class: CompositeOwnerHasOneChild::class,
            id: 100,
        );

        self::assertInstanceOf(
            CompositeOwner::class,
            $child->owner,
        );

        self::assertSame('alpha', $child->owner->name);
    }

    public function testCompositeChildOfSinglePkParentCanBeFetchedByCompositeKey(): void
    {
        $child = $this->modelsManager->fetchByCompositeKey(
            class: CompositeChildOfSingleParent::class,
            keys: [
                'scope' => 'user',
                'name' => 'alice.settings',
            ],
        );

        self::assertInstanceOf(
            CompositeChildOfSingleParent::class,
            $child,
        );

        self::assertSame('alice user settings', $child->label);
        self::assertSame(1, $child->userId);
    }

    public function testCompositeChildOfSinglePkParentLoadsSinglePkParent(): void
    {
        $child = $this->modelsManager->fetchByCompositeKey(
            class: CompositeChildOfSingleParent::class,
            keys: [
                'scope' => 'user',
                'name' => 'alice.settings',
            ],
        );

        self::assertInstanceOf(
            User::class,
            $child->user,
        );

        self::assertSame('Alice', $child->user->name);
    }

    public function testSaveCompositeOwnerRoundTrip(): void
    {
        $owner = new CompositeOwner();
        $owner->scope = 'runtime';
        $owner->name = 'gamma';
        $owner->label = 'Runtime Gamma';

        (void) $this->modelsManager->save($owner);

        $fetched = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'runtime',
                'name' => 'gamma',
            ],
        );

        self::assertSame('Runtime Gamma', $fetched->label);
    }

    public function testSaveCompositeOwnerAndBelongsToChildPopulatesForeignKeyColumns(): void
    {
        $owner = new CompositeOwner();
        $owner->scope = 'runtime';
        $owner->name = 'delta';
        $owner->label = 'Runtime Delta';

        (void) $this->modelsManager->save($owner);

        $child = new CompositeOwnerHasManyChild();
        $child->ownerScope = $owner->scope;
        $child->ownerName = $owner->name;
        $child->label = 'delta only child';

        $savedChild = $this->modelsManager->save($child);

        $fetched = $this->modelsManager->fetchById(
            class: CompositeOwnerHasManyChild::class,
            id: $savedChild->id ?? throw new \LogicException('child id not populated'),
        );

        self::assertSame('runtime', $fetched->ownerScope);
        self::assertSame('delta', $fetched->ownerName);
    }

    public function testAggregateSaveCompositeOwnerWithHasManyChildrenPropagatesForeignKeys(): void
    {
        $owner = new CompositeOwner();
        $owner->scope = 'aggregate';
        $owner->name = 'epsilon';
        $owner->label = 'Aggregate Epsilon';
        $owner->manyChildren = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: CompositeOwnerHasManyChild::class,
        );

        $firstChild = new CompositeOwnerHasManyChild();
        $firstChild->label = 'epsilon child one';

        $secondChild = new CompositeOwnerHasManyChild();
        $secondChild->label = 'epsilon child two';

        $owner->manyChildren->add($firstChild);
        $owner->manyChildren->add($secondChild);

        (void) $this->modelsManager->save(
            model: $owner,
            scope: ValidationScope::AGGREGATE,
        );

        $refetched = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'aggregate',
                'name' => 'epsilon',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $refetched->manyChildren,
        );

        self::assertSame(
            2,
            $refetched->manyChildren->count(),
        );

        $labels = [];

        foreach ($refetched->manyChildren as $child) {
            $labels[] = $child->label;
            self::assertSame('aggregate', $child->ownerScope);
            self::assertSame('epsilon', $child->ownerName);
        }

        \sort($labels);

        self::assertSame(
            [
                'epsilon child one',
                'epsilon child two',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerBelongsToManyLazyLoadsCompositeTargets(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $owner->tags,
        );

        self::assertSame(
            2,
            $owner->tags->count(),
        );

        $labels = [];

        foreach ($owner->tags as $tag) {
            self::assertInstanceOf(CompositeTag::class, $tag);
            $labels[] = $tag->label;
        }

        \sort($labels);

        self::assertSame(
            [
                'Large',
                'Red',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerBelongsToManyLazyLoadsPartitionsPerParent(): void
    {
        $beta = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $beta->tags,
        );

        self::assertSame(
            1,
            $beta->tags->count(),
        );

        $labels = [];

        foreach ($beta->tags as $tag) {
            $labels[] = $tag->label;
        }

        self::assertSame(
            [
                'Blue',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerBelongsToManyEagerLoadsAllTargetsAcrossParents(): void
    {
        /** @var list<CompositeOwner> $owners */
        $owners = \iterator_to_array($this->modelsManager->findAll(
            class: CompositeOwner::class,
            with: [
                'tags' => null,
            ],
        ), preserve_keys: false);

        self::assertSame(2, \sizeof($owners));

        $byName = [];

        foreach ($owners as $owner) {
            $byName[$owner->name] = $owner;
        }

        self::assertArrayHasKey('alpha', $byName);
        self::assertArrayHasKey('beta', $byName);

        $alphaLabels = [];

        foreach ($byName['alpha']->tags ?? [] as $tag) {
            $alphaLabels[] = $tag->label;
        }

        \sort($alphaLabels);

        self::assertSame(
            [
                'Large',
                'Red',
            ],
            $alphaLabels,
        );

        $betaLabels = [];

        foreach ($byName['beta']->tags ?? [] as $tag) {
            $betaLabels[] = $tag->label;
        }

        self::assertSame(
            [
                'Blue',
            ],
            $betaLabels,
        );
    }

    public function testCompositeOwnerBelongsToManyPivotAddInsertsRow(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        $extraTag = $this->modelsManager->fetchByCompositeKey(
            class: CompositeTag::class,
            keys: [
                'realm' => 'size',
                'code' => 'large',
            ],
        );

        self::assertInstanceOf(Relation::class, $owner->tags);
        $owner->tags->add($extraTag);

        (void) $this->modelsManager->save($owner);

        $refetched = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        self::assertInstanceOf(Relation::class, $refetched->tags);

        self::assertSame(
            2,
            $refetched->tags->count(),
        );

        $labels = [];

        foreach ($refetched->tags as $tag) {
            $labels[] = $tag->label;
        }

        \sort($labels);

        self::assertSame(
            [
                'Blue',
                'Large',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerMorphManyLazyLoadsNotesFilteredByCompositeIdColumns(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(Relation::class, $owner->notes);
        self::assertSame(2, $owner->notes->count());

        $bodies = [];

        foreach ($owner->notes as $note) {
            self::assertInstanceOf(CompositeMorphNote::class, $note);
            $bodies[] = $note->body;
        }

        \sort($bodies);

        self::assertSame(
            [
                'alpha note one',
                'alpha note two',
            ],
            $bodies,
        );
    }

    public function testCompositeOwnerMorphManyEagerLoadsPartitionedByParentTuple(): void
    {
        /** @var list<CompositeOwner> $owners */
        $owners = \iterator_to_array($this->modelsManager->findAll(
            class: CompositeOwner::class,
            with: [
                'notes' => null,
            ],
        ), preserve_keys: false);

        $byName = [];

        foreach ($owners as $owner) {
            $byName[$owner->name] = $owner;
        }

        self::assertArrayHasKey('alpha', $byName);
        self::assertArrayHasKey('beta', $byName);

        $alphaBodies = [];

        foreach ($byName['alpha']->notes ?? [] as $note) {
            $alphaBodies[] = $note->body;
        }

        \sort($alphaBodies);

        self::assertSame(
            [
                'alpha note one',
                'alpha note two',
            ],
            $alphaBodies,
        );

        $betaBodies = [];

        foreach ($byName['beta']->notes ?? [] as $note) {
            $betaBodies[] = $note->body;
        }

        self::assertSame(
            [
                'beta note one',
            ],
            $betaBodies,
        );
    }

    public function testCompositeOwnerMorphManySaveWritesTypeAndCompositeIdColumns(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        self::assertInstanceOf(Relation::class, $owner->notes);

        $newNote = new CompositeMorphNote();
        $newNote->body = 'beta note two';

        $owner->notes->add($newNote);

        (void) $this->modelsManager->save($owner);

        self::assertSame(CompositeOwner::class, $newNote->ownerType);
        self::assertSame('system', $newNote->ownerScope);
        self::assertSame('beta', $newNote->ownerName);

        $refetched = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        self::assertInstanceOf(Relation::class, $refetched->notes);
        self::assertSame(2, $refetched->notes->count());
    }

    public function testCompositeOwnerMorphToManyLazyLoadsThroughPolymorphicPivot(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(Relation::class, $owner->morphTags);
        self::assertSame(2, $owner->morphTags->count());

        $labels = [];

        foreach ($owner->morphTags as $tag) {
            self::assertInstanceOf(CompositeTag::class, $tag);
            $labels[] = $tag->label;
        }

        \sort($labels);

        self::assertSame(
            [
                'Large',
                'Red',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerMorphToManyEagerLoadsPartitionedByParentTuple(): void
    {
        /** @var list<CompositeOwner> $owners */
        $owners = \iterator_to_array($this->modelsManager->findAll(
            class: CompositeOwner::class,
            with: [
                'morphTags' => null,
            ],
        ), preserve_keys: false);

        $byName = [];

        foreach ($owners as $owner) {
            $byName[$owner->name] = $owner;
        }

        $alphaLabels = [];

        foreach ($byName['alpha']->morphTags ?? [] as $tag) {
            $alphaLabels[] = $tag->label;
        }

        \sort($alphaLabels);

        self::assertSame(
            [
                'Large',
                'Red',
            ],
            $alphaLabels,
        );

        $betaLabels = [];

        foreach ($byName['beta']->morphTags ?? [] as $tag) {
            $betaLabels[] = $tag->label;
        }

        self::assertSame(
            [
                'Blue',
            ],
            $betaLabels,
        );
    }

    public function testCompositeOwnerMorphToManyPivotAddInsertsRowWithTypeAndCompositeKeys(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        $extraTag = $this->modelsManager->fetchByCompositeKey(
            class: CompositeTag::class,
            keys: [
                'realm' => 'size',
                'code' => 'large',
            ],
        );

        self::assertInstanceOf(Relation::class, $owner->morphTags);
        $owner->morphTags->add($extraTag);

        (void) $this->modelsManager->save($owner);

        $refetched = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'beta',
            ],
        );

        self::assertInstanceOf(Relation::class, $refetched->morphTags);
        self::assertSame(2, $refetched->morphTags->count());

        $labels = [];

        foreach ($refetched->morphTags as $tag) {
            $labels[] = $tag->label;
        }

        \sort($labels);

        self::assertSame(
            [
                'Blue',
                'Large',
            ],
            $labels,
        );
    }

    public function testCompositeOwnerBelongsToManyPivotRemoveDeletesRow(): void
    {
        $owner = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(Relation::class, $owner->tags);

        $victim = null;

        foreach ($owner->tags as $tag) {
            if ($tag->realm === 'color' && $tag->code === 'red') {
                $victim = $tag;

                break;
            }
        }

        self::assertInstanceOf(CompositeTag::class, $victim);
        $owner->tags->remove($victim);

        (void) $this->modelsManager->save($owner);

        $refetched = $this->modelsManager->fetchByCompositeKey(
            class: CompositeOwner::class,
            keys: [
                'scope' => 'system',
                'name' => 'alpha',
            ],
        );

        self::assertInstanceOf(Relation::class, $refetched->tags);

        self::assertSame(
            1,
            $refetched->tags->count(),
        );

        $labels = [];

        foreach ($refetched->tags as $tag) {
            $labels[] = $tag->label;
        }

        self::assertSame(
            [
                'Large',
            ],
            $labels,
        );
    }

    public function testWhereHasComposesCompositePivotExistsSubquery(): void
    {
        $withTags = $this->modelsManager
            ->query(CompositeOwner::class)
            ->whereHas(relationName: 'tags');

        self::assertSame(2, $withTags->count());
    }
}
