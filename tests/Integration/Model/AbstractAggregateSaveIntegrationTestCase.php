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

use Fixture\Model\Aggregate\AggregateFailingModel;
use Fixture\Model\Category;
use Fixture\Model\Country;
use Fixture\Model\Polymorphic\Article;
use Fixture\Model\Polymorphic\Avatar;
use Fixture\Model\Polymorphic\Employee;
use Fixture\Model\Polymorphic\PolyComment;
use Fixture\Model\Polymorphic\PolyTag;
use Fixture\Model\Post;
use Fixture\Model\Profile;
use Fixture\Model\Role;
use Fixture\Model\User;
use Fixture\Validator\FixtureViolationCode;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;
use Tuxxedo\Model\ValidationScope;
use Tuxxedo\Validator\ValidationException;

abstract class AbstractAggregateSaveIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAllFixtureTables();

        $this->createPolymorphicTables();

        $this->modelsManager->createTable(
            modelClass: AggregateFailingModel::class,
        )->execute();
    }

    private function createPolymorphicTables(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->articlesPolymorphicSchemaSql(),
            native: true,
        );

        $this->connection->query(
            sql: $this->schemaProvider->polyCommentsSchemaSql(),
            native: true,
        );

        $this->connection->query(
            sql: $this->schemaProvider->polyTagsSchemaSql(),
            native: true,
        );

        $this->connection->query(
            sql: $this->schemaProvider->polyTaggablesPivotSchemaSql(),
            native: true,
        );

        $this->connection->query(
            sql: $this->schemaProvider->employeesPolymorphicSchemaSql(),
            native: true,
        );

        $this->connection->query(
            sql: $this->schemaProvider->avatarsPolymorphicSchemaSql(),
            native: true,
        );
    }

    public function testAggregateSaveInSelfScopeSavesRootOnlyAndUnchanged(): void
    {
        $user = new User();
        $user->name = 'Alice';
        $user->email = 'alice@example.test';

        $saved = $this->modelsManager->save(
            model: $user,
        );

        self::assertNotNull($saved->id);
        self::assertSame(1, $this->countRows(table: 'users'));
        self::assertSame(0, $this->countRows(table: 'profiles'));
        self::assertSame(0, $this->countRows(table: 'countries'));
        self::assertSame(0, $this->countRows(table: 'posts'));
    }

    public function testAggregateSaveHasOneAssignsForeignKeyOnChild(): void
    {
        $user = new User();
        $user->name = 'Bob';
        $user->email = 'bob@example.test';

        $profile = new Profile();
        $profile->bio = 'about bob';
        $user->profile = $profile;

        $saved = $this->modelsManager->save(
            model: $user,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($profile->id);
        self::assertSame($saved->id, $profile->userId);
        self::assertSame(1, $this->countRows(table: 'users'));
        self::assertSame(1, $this->countRows(table: 'profiles'));
    }

    public function testAggregateSaveBelongsToPropagatesForeignKeyToParent(): void
    {
        $country = new Country();
        $country->name = 'Denmark';
        $country->code = 'DK';

        $user = new User();
        $user->name = 'Carol';
        $user->email = 'carol@example.test';
        $user->country = $country;

        $saved = $this->modelsManager->save(
            model: $user,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($country->id);
        self::assertNotNull($saved->id);
        self::assertSame($country->id, $saved->countryId);
        self::assertSame(1, $this->countRows(table: 'countries'));
        self::assertSame(1, $this->countRows(table: 'users'));
    }

    public function testAggregateSaveHasManyChildrenGetForeignKey(): void
    {
        $user = new User();
        $user->name = 'Dave';
        $user->email = 'dave@example.test';
        $user->posts = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: Post::class,
        );

        $postA = new Post();
        $postA->title = 'first';
        $postB = new Post();
        $postB->title = 'second';
        $user->posts->add($postA);
        $user->posts->add($postB);

        $saved = $this->modelsManager->save(
            model: $user,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($postA->id);
        self::assertNotNull($postB->id);
        self::assertSame($saved->id, $postA->userId);
        self::assertSame($saved->id, $postB->userId);
        self::assertSame(2, $this->countRows(table: 'posts'));
    }

    public function testAggregateSaveBelongsToManyFlushesPivotRows(): void
    {
        $user = new User();
        $user->name = 'Eve';
        $user->email = 'eve@example.test';
        $user->roles = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: Role::class,
        );

        $roleA = new Role();
        $roleA->key = 'admin';
        $roleA->label = 'Admin';
        $roleB = new Role();
        $roleB->key = 'editor';
        $roleB->label = 'Editor';
        $user->roles->add($roleA);
        $user->roles->add($roleB);

        $saved = $this->modelsManager->save(
            model: $user,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($roleA->id);
        self::assertNotNull($roleB->id);
        self::assertSame(2, $this->countRows(table: 'roles'));
        self::assertSame(2, $this->countRows(table: 'user_role'));
    }

    public function testAggregateSaveMorphOneAssignsTypeAndIdOnChild(): void
    {
        $employee = new Employee();
        $employee->name = 'Frank';

        $avatar = new Avatar();
        $avatar->url = 'https://example.test/frank.png';
        $employee->avatar = $avatar;

        $saved = $this->modelsManager->save(
            model: $employee,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($avatar->id);
        self::assertSame(Employee::class, $avatar->subjectType);
        self::assertSame($saved->id, $avatar->subjectId);
    }

    public function testAggregateSaveMorphManyChildrenGetTypeAndId(): void
    {
        $article = new Article();
        $article->title = 'aggregate morph many';
        $article->userId = 1;
        $article->comments = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyComment::class,
        );

        $commentA = new PolyComment();
        $commentA->body = 'first';
        $commentB = new PolyComment();
        $commentB->body = 'second';
        $article->comments->add($commentA);
        $article->comments->add($commentB);

        $saved = $this->modelsManager->save(
            model: $article,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($commentA->id);
        self::assertNotNull($commentB->id);
        self::assertSame(Article::class, $commentA->commentableType);
        self::assertSame(Article::class, $commentB->commentableType);
        self::assertSame($saved->id, $commentA->commentableId);
        self::assertSame($saved->id, $commentB->commentableId);
    }

    public function testAggregateSaveMorphToManyFlushesPivotWithTypeAndId(): void
    {
        $article = new Article();
        $article->title = 'aggregate morph to many';
        $article->userId = 1;
        $article->tags = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: PolyTag::class,
        );

        $tagA = new PolyTag();
        $tagA->name = 'featured';
        $tagB = new PolyTag();
        $tagB->name = 'news';
        $article->tags->add($tagA);
        $article->tags->add($tagB);

        $saved = $this->modelsManager->save(
            model: $article,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($tagA->id);
        self::assertNotNull($tagB->id);
        self::assertSame(2, $this->countRows(table: 'poly_taggables'));
    }

    public function testAggregateSaveMorphToPropagatesForeignKeyToParent(): void
    {
        $article = new Article();
        $article->title = 'target-for-morph-to';
        $article->userId = 1;

        $comment = new PolyComment();
        $comment->body = 'hosts article via morph-to';
        $comment->commentable = $article;

        $saved = $this->modelsManager->save(
            model: $comment,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($article->id);
        self::assertNotNull($saved->id);
        self::assertSame(Article::class, $saved->commentableType);
        self::assertSame($article->id, $saved->commentableId);
    }

    public function testAggregateSaveSkipsUnloadedRelationsOnRoot(): void
    {
        $user = new User();
        $user->name = 'Gina';
        $user->email = 'gina@example.test';

        $saved = $this->modelsManager->save(
            model: $user,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertSame(1, $this->countRows(table: 'users'));
        self::assertSame(0, $this->countRows(table: 'countries'));
        self::assertSame(0, $this->countRows(table: 'profiles'));
        self::assertSame(0, $this->countRows(table: 'posts'));
        self::assertSame(0, $this->countRows(table: 'roles'));
        self::assertSame(0, $this->countRows(table: 'user_role'));
    }

    public function testAggregateSaveFailingValidationRaisesAndWritesNothing(): void
    {
        $failing = new AggregateFailingModel();
        $failing->name = 'anything';

        try {
            (void) $this->modelsManager->save(
                model: $failing,
                scope: ValidationScope::AGGREGATE,
            );

            self::fail('Expected ValidationException from failing model');
        } catch (ValidationException $exception) {
            self::assertCount(1, $exception->violations);
            self::assertSame('name', $exception->violations[0]->propertyPath);
            self::assertSame(
                FixtureViolationCode::ALWAYS_FAIL,
                $exception->violations[0]->code,
            );
        }

        self::assertSame(0, $this->countRows(table: 'aggregate_failing_models'));
    }

    public function testAggregateSaveThrowsForInstanceLevelCycle(): void
    {
        $catA = new Category();
        $catA->name = 'A';
        $catB = new Category();
        $catB->name = 'B';
        $catA->parent = $catB;
        $catB->parent = $catA;

        try {
            (void) $this->modelsManager->save(
                model: $catA,
                scope: ValidationScope::AGGREGATE,
            );

            self::fail('Expected ModelException for instance-level cycle');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'cycle',
                \strtolower($exception->getMessage()),
            );
        }

        self::assertSame(0, $this->countRows(table: 'categories'));
    }

    public function testAggregateSaveWithBidirectionalPostAuthorProducesStableOrder(): void
    {
        $user = new User();
        $user->name = 'Harry';
        $user->email = 'harry@example.test';
        $user->posts = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
            modelClass: Post::class,
        );

        $post = new Post();
        $post->title = 'hello';
        $post->author = $user;
        $user->posts->add($post);

        $saved = $this->modelsManager->save(
            model: $user,
            scope: ValidationScope::AGGREGATE,
        );

        self::assertNotNull($saved->id);
        self::assertNotNull($post->id);
        self::assertSame($saved->id, $post->userId);
        self::assertSame(1, $this->countRows(table: 'users'));
        self::assertSame(1, $this->countRows(table: 'posts'));
    }

    private function countRows(
        string $table,
    ): int {
        return $this->connection->count($table)->count();
    }
}
