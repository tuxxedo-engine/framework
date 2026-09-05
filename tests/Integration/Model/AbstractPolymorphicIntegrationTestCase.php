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

use Fixture\Model\Polymorphic\Article;
use Fixture\Model\Polymorphic\Avatar;
use Fixture\Model\Polymorphic\BulkDeleteBoard;
use Fixture\Model\Polymorphic\CascadeSaveComment;
use Fixture\Model\Polymorphic\Employee;
use Fixture\Model\Polymorphic\HostedComment;
use Fixture\Model\Polymorphic\LocalKeyPoster;
use Fixture\Model\Polymorphic\MappedArticle;
use Fixture\Model\Polymorphic\MappedComment;
use Fixture\Model\Polymorphic\NullableAvatar;
use Fixture\Model\Polymorphic\OrphanFeed;
use Fixture\Model\Polymorphic\PlainAvatar;
use Fixture\Model\Polymorphic\PolyComment;
use Fixture\Model\Polymorphic\PolyTag;
use Fixture\Model\Polymorphic\Poster;
use Fixture\Model\Polymorphic\RestrictArticle;
use Fixture\Model\Polymorphic\SetNullOwner;
use Fixture\Model\Polymorphic\SimpleHost;
use Fixture\Model\Polymorphic\StrictComment;
use Fixture\Model\Polymorphic\StrictPoster;
use Fixture\Model\Polymorphic\Video;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

abstract class AbstractPolymorphicIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createArticlesTable();
        $this->createVideosTable();
        $this->createPolyCommentsTable();
        $this->createPolyTagsTable();
        $this->createPolyTaggablesPivot();
        $this->createEmployeesTable();
        $this->createAvatarsTable();
        $this->createMappedArticlesTable();
        $this->createMappedCommentsTable();
        $this->createRestrictArticlesTable();
        $this->createNullableAvatarsTable();
        $this->createSetNullOwnersTable();
        $this->createBulkDeleteBoardsTable();
        $this->createOrphanFeedsTable();
        $this->createStrictCommentsTable();
        $this->createCascadeSaveCommentsTable();
        $this->createPlainAvatarsTable();
        $this->createPostersTable();
        $this->createSimpleHostsTable();
        $this->createHostedCommentsTable();
        $this->createStrictPostersTable();
        $this->createLocalKeyPostersTable();

        $this->seedUser(id: 1, name: 'Alice');
        $this->seedUser(id: 2, name: 'Bob');

        $this->seedArticle(id: 10, userId: 1, title: 'Alice article');
        $this->seedArticle(id: 11, userId: 2, title: 'Bob article');
        $this->seedVideo(id: 20, userId: 1, title: 'Alice video');

        $this->seedPolyComment(id: 100, commentableType: Article::class, commentableId: 10, body: 'first on alice article');
        $this->seedPolyComment(id: 101, commentableType: Article::class, commentableId: 10, body: 'second on alice article');
        $this->seedPolyComment(id: 102, commentableType: Article::class, commentableId: 11, body: 'on bob article');
        $this->seedPolyComment(id: 103, commentableType: Video::class, commentableId: 20, body: 'on alice video');

        $this->seedPolyTag(id: 200, name: 'featured');
        $this->seedPolyTag(id: 201, name: 'draft');

        $this->seedPolyTaggable(articleId: 10, tagId: 200);
        $this->seedPolyTaggable(articleId: 10, tagId: 201);
        $this->seedPolyTaggable(articleId: 11, tagId: 200);

        $this->seedEmployee(id: 500, name: 'Alice Employee');
        $this->seedAvatar(id: 600, subjectType: Employee::class, subjectId: 500, url: 'https://example.test/alice.png');
    }

    protected function createArticlesTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->articlesPolymorphicSchemaSql(),
            native: true,
        );
    }

    protected function createVideosTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->videosPolymorphicSchemaSql(),
            native: true,
        );
    }

    protected function createPolyCommentsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->polyCommentsSchemaSql(),
            native: true,
        );
    }

    protected function createPolyTagsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->polyTagsSchemaSql(),
            native: true,
        );
    }

    protected function createPolyTaggablesPivot(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->polyTaggablesPivotSchemaSql(),
            native: true,
        );
    }

    protected function createEmployeesTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->employeesPolymorphicSchemaSql(),
            native: true,
        );
    }

    protected function createAvatarsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->avatarsPolymorphicSchemaSql(),
            native: true,
        );
    }

    protected function createMappedArticlesTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->mappedArticlesSchemaSql(),
            native: true,
        );
    }

    protected function createMappedCommentsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->mappedCommentsSchemaSql(),
            native: true,
        );
    }

    protected function createRestrictArticlesTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->restrictArticlesSchemaSql(),
            native: true,
        );
    }

    protected function createNullableAvatarsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->nullableAvatarsSchemaSql(),
            native: true,
        );
    }

    protected function createSetNullOwnersTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->setNullOwnersSchemaSql(),
            native: true,
        );
    }

    protected function createBulkDeleteBoardsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->bulkDeleteBoardsSchemaSql(),
            native: true,
        );
    }

    protected function createOrphanFeedsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->orphanFeedsSchemaSql(),
            native: true,
        );
    }

    protected function createStrictCommentsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->strictCommentsSchemaSql(),
            native: true,
        );
    }

    protected function createCascadeSaveCommentsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->cascadeSaveCommentsSchemaSql(),
            native: true,
        );
    }

    protected function createPlainAvatarsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->plainAvatarsSchemaSql(),
            native: true,
        );
    }

    protected function createPostersTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->postersSchemaSql(),
            native: true,
        );
    }

    protected function createSimpleHostsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->simpleHostsSchemaSql(),
            native: true,
        );
    }

    protected function createHostedCommentsTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->hostedCommentsSchemaSql(),
            native: true,
        );
    }

    protected function createStrictPostersTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->strictPostersSchemaSql(),
            native: true,
        );
    }

    protected function createLocalKeyPostersTable(): void
    {
        $this->connection->query(
            sql: $this->schemaProvider->localKeyPostersSchemaSql(),
            native: true,
        );
    }

    private function seedUser(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(table: 'users')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->set(column: 'email', value: \strtolower($name) . '@example.test')
            ->set(column: 'isActive', value: 1)
            ->set(column: 'postCount', value: 0)
            ->set(column: 'score', value: 0.0)
            ->execute();
    }

    private function seedArticle(
        int $id,
        int $userId,
        string $title,
    ): void {
        $this->connection->insert(table: 'articles')
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->execute();
    }

    private function seedVideo(
        int $id,
        int $userId,
        string $title,
    ): void {
        $this->connection->insert(table: 'videos')
            ->set(column: 'id', value: $id)
            ->set(column: 'user_id', value: $userId)
            ->set(column: 'title', value: $title)
            ->execute();
    }

    private function seedPolyComment(
        int $id,
        string $commentableType,
        int $commentableId,
        string $body,
    ): void {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: $id)
            ->set(column: 'commentable_type', value: $commentableType)
            ->set(column: 'commentable_id', value: $commentableId)
            ->set(column: 'body', value: $body)
            ->execute();
    }

    private function seedPolyTag(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(table: 'poly_tags')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedPolyTaggable(
        int $articleId,
        int $tagId,
    ): void {
        $this->connection->insert(table: 'poly_taggables')
            ->set(column: 'tag_id', value: $tagId)
            ->set(column: 'taggable_type', value: Article::class)
            ->set(column: 'taggable_id', value: $articleId)
            ->execute();
    }

    private function seedEmployee(
        int $id,
        string $name,
    ): void {
        $this->connection->insert(table: 'employees')
            ->set(column: 'id', value: $id)
            ->set(column: 'name', value: $name)
            ->execute();
    }

    private function seedAvatar(
        int $id,
        string $subjectType,
        int $subjectId,
        string $url,
    ): void {
        $this->connection->insert(table: 'avatars')
            ->set(column: 'id', value: $id)
            ->set(column: 'subject_type', value: $subjectType)
            ->set(column: 'subject_id', value: $subjectId)
            ->set(column: 'url', value: $url)
            ->execute();
    }

    public function testLazyMorphManyIterationYieldsBodies(): void
    {
        $article = $this->modelsManager->fetchById(
            class: Article::class,
            id: 10,
        );

        self::assertInstanceOf(Relation::class, $article->comments);

        $bodies = [];

        foreach ($article->comments as $comment) {
            $bodies[] = $comment->body;
        }

        \sort($bodies);

        self::assertSame(
            [
                'first on alice article',
                'second on alice article',
            ],
            $bodies,
        );
    }

    public function testLazyMorphManyCountReflectsTypedFilter(): void
    {
        $article = $this->modelsManager->fetchById(
            class: Article::class,
            id: 11,
        );

        self::assertInstanceOf(Relation::class, $article->comments);
        self::assertSame(1, $article->comments->count());
    }

    public function testLazyMorphToResolvesArticleTarget(): void
    {
        $comment = $this->modelsManager->fetchById(
            class: PolyComment::class,
            id: 100,
        );

        self::assertInstanceOf(Article::class, $comment->commentable);
        self::assertSame(10, $comment->commentable->id);
        self::assertSame('Alice article', $comment->commentable->title);
    }

    public function testLazyMorphToResolvesVideoTarget(): void
    {
        $comment = $this->modelsManager->fetchById(
            class: PolyComment::class,
            id: 103,
        );

        self::assertInstanceOf(Video::class, $comment->commentable);
        self::assertSame(20, $comment->commentable->id);
    }

    public function testLazyMorphOneResolvesAvatar(): void
    {
        $employee = $this->modelsManager->fetchById(
            class: Employee::class,
            id: 500,
        );

        self::assertInstanceOf(Avatar::class, $employee->avatar);
        self::assertSame('https://example.test/alice.png', $employee->avatar->url);
    }

    public function testLazyMorphToManyIterationYieldsTags(): void
    {
        $article = $this->modelsManager->fetchById(
            class: Article::class,
            id: 10,
        );

        self::assertInstanceOf(Relation::class, $article->tags);

        $names = [];

        foreach ($article->tags as $tag) {
            $names[] = $tag->name;
        }

        \sort($names);

        self::assertSame(
            [
                'draft',
                'featured',
            ],
            $names,
        );
    }

    public function testCascadeSaveAssignsTypeAndIdOnMorphManyChild(): void
    {
        $article = $this->modelsManager->fetchById(
            class: Article::class,
            id: 10,
        );

        self::assertInstanceOf(Relation::class, $article->comments);

        $newComment = new PolyComment();
        $newComment->body = 'freshly added';

        $article->comments->add($newComment);

        (void) $this->modelsManager->save($article);

        $persisted = $this->modelsManager->findFirst(
            class: PolyComment::class,
            criteria: static function (WhereStatementInterface $statement): void {
                $statement->where('body', 'freshly added');
            },
        );

        self::assertInstanceOf(PolyComment::class, $persisted);
        self::assertSame(Article::class, $persisted->commentableType);
        self::assertSame(10, $persisted->commentableId);
    }

    public function testCascadeDeleteRemovesMorphManyChildren(): void
    {
        $article = $this->modelsManager->fetchById(
            class: Article::class,
            id: 10,
        );

        (void) $this->modelsManager->delete($article);

        $count = $this->connection->count(table: 'poly_comments')
            ->where('commentable_type', Article::class)
            ->where('commentable_id', 10)
            ->count();

        self::assertSame(0, $count);
    }

    public function testCascadeDeleteRemovesMorphToManyPivotRows(): void
    {
        $article = $this->modelsManager->fetchById(
            class: Article::class,
            id: 10,
        );

        (void) $this->modelsManager->delete($article);

        $count = $this->connection->count(table: 'poly_taggables')
            ->where('taggable_type', Article::class)
            ->where('taggable_id', 10)
            ->count();

        self::assertSame(0, $count);
    }

    public function testEagerMorphManyPrefetchesComments(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'comments' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $countsByArticleId = [];

        foreach ($articles as $article) {
            self::assertNotNull($article->id);
            self::assertInstanceOf(Relation::class, $article->comments);

            $countsByArticleId[$article->id] = $article->comments->count();
        }

        self::assertSame(2, $countsByArticleId[10] ?? null);
        self::assertSame(1, $countsByArticleId[11] ?? null);
    }

    public function testEagerMorphToGroupsByType(): void
    {
        $comments = \iterator_to_array(
            $this->modelsManager->query(PolyComment::class)
                ->with(
                    with: [
                        'commentable' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $articleCount = 0;
        $videoCount = 0;

        foreach ($comments as $comment) {
            if ($comment->commentable instanceof Article) {
                $articleCount++;
            } elseif ($comment->commentable instanceof Video) {
                $videoCount++;
            }
        }

        self::assertSame(3, $articleCount);
        self::assertSame(1, $videoCount);
    }

    public function testNestedEagerMorphToDotAuthor(): void
    {
        $comments = \iterator_to_array(
            $this->modelsManager->query(PolyComment::class)
                ->with(
                    with: [
                        'commentable.author' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $authorsByCommentId = [];

        foreach ($comments as $comment) {
            self::assertNotNull($comment->id);

            $commentable = $comment->commentable;

            if ($commentable instanceof Article || $commentable instanceof Video) {
                $authorsByCommentId[$comment->id] = $commentable->author?->name;
            }
        }

        self::assertSame('Alice', $authorsByCommentId[100] ?? null);
        self::assertSame('Alice', $authorsByCommentId[101] ?? null);
        self::assertSame('Bob', $authorsByCommentId[102] ?? null);
        self::assertSame('Alice', $authorsByCommentId[103] ?? null);
    }

    public function testLazyMorphToResolvesViaTypeMapAlias(): void
    {
        $article = new MappedArticle();
        $article->title = 'Mapped article';
        $saved = $this->modelsManager->save($article);

        self::assertNotNull($saved->id);

        $this->connection->insert(table: 'mapped_comments')
            ->set(column: 'commentable_type', value: 'article')
            ->set(column: 'commentable_id', value: $saved->id)
            ->set(column: 'body', value: 'mapped body')
            ->execute();

        $comment = $this->modelsManager->query(MappedComment::class)
            ->first();

        self::assertInstanceOf(MappedComment::class, $comment);
        self::assertInstanceOf(MappedArticle::class, $comment->commentable);
        self::assertSame('Mapped article', $comment->commentable->title);
    }

    public function testLazyMorphToThrowsWhenTypeMapAliasUnknown(): void
    {
        $this->connection->insert(table: 'mapped_comments')
            ->set(column: 'commentable_type', value: 'unknown-alias')
            ->set(column: 'commentable_id', value: 1)
            ->set(column: 'body', value: 'bad alias')
            ->execute();

        try {
            (void) $this->modelsManager->query(MappedComment::class)
                ->first();

            self::fail('Expected ModelException for unresolvable type alias');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'cannot resolve type value',
                $exception->getMessage(),
            );
        }
    }

    public function testCascadeSaveEncodesViaTypeMapAlias(): void
    {
        $article = new MappedArticle();
        $article->title = 'Fresh mapped article';

        $comment = new MappedComment();
        $comment->body = 'via alias';

        $article->comments = Relation::createFromPrefetched(
            values: [],
            manager: $this->modelsManager,
            modelClass: MappedComment::class,
        );
        $article->comments->add($comment);

        $saved = $this->modelsManager->save($article);

        $persisted = $this->modelsManager->findFirst(
            class: MappedComment::class,
            criteria: static function (WhereStatementInterface $s): void {
                $s->where('body', 'via alias');
            },
        );

        self::assertInstanceOf(MappedComment::class, $persisted);
        self::assertSame('article', $persisted->commentableType);
        self::assertSame($saved->id, $persisted->commentableId);
    }

    public function testLazyMorphToThrowsWhenTypeColumnStoresNonExistentClass(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 900)
            ->set(column: 'commentable_type', value: 'App\\Nope')
            ->set(column: 'commentable_id', value: 1)
            ->set(column: 'body', value: 'unresolvable fqcn')
            ->execute();

        try {
            (void) $this->modelsManager->fetchById(class: PolyComment::class, id: 900);

            self::fail('Expected ModelException for non-existent class');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'cannot resolve type value',
                $exception->getMessage(),
            );
        }
    }

    public function testCascadeSaveMorphOneAssignsTypeAndIdOnChild(): void
    {
        $employee = new Employee();
        $employee->name = 'New Employee';

        $avatar = new Avatar();
        $avatar->url = 'https://example.test/new.png';
        $employee->avatar = $avatar;

        $saved = $this->modelsManager->save($employee);

        $persisted = $this->modelsManager->findFirst(
            class: Avatar::class,
            criteria: static function (WhereStatementInterface $s): void {
                $s->where('url', 'https://example.test/new.png');
            },
        );

        self::assertInstanceOf(Avatar::class, $persisted);
        self::assertSame(Employee::class, $persisted->subjectType);
        self::assertSame($saved->id, $persisted->subjectId);
    }

    public function testCascadeSaveMorphToSavesTargetFirstThenWritesTypeAndId(): void
    {
        $newArticle = new Article();
        $newArticle->title = 'Article born by cascade';
        $newArticle->userId = 1;

        $comment = new CascadeSaveComment();
        $comment->body = 'attached during cascade';
        $comment->commentable = $newArticle;

        $savedComment = $this->modelsManager->save($comment);

        self::assertSame(Article::class, $savedComment->commentableType);
        self::assertGreaterThan(0, $savedComment->commentableId);

        $persistedArticle = $this->modelsManager->findById(
            class: Article::class,
            id: $savedComment->commentableId,
        );

        self::assertInstanceOf(Article::class, $persistedArticle);
        self::assertSame('Article born by cascade', $persistedArticle->title);
    }

    public function testCascadeSaveMorphManyEarlyReturnsForUninitializedRelation(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 11);

        (void) $this->modelsManager->save($article);

        $count = $this->connection->count(table: 'poly_comments')
            ->where('commentable_type', Article::class)
            ->where('commentable_id', 11)
            ->count();

        self::assertSame(1, $count);
    }

    public function testCascadeSaveMorphManyRemoveOrphanDeletesRemoved(): void
    {
        $feed = new OrphanFeed();
        $feed->name = 'feed-a';
        $saved = $this->modelsManager->save($feed);

        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 800)
            ->set(column: 'commentable_type', value: OrphanFeed::class)
            ->set(column: 'commentable_id', value: $saved->id)
            ->set(column: 'body', value: 'to be orphaned')
            ->execute();

        $feed = $this->modelsManager->fetchById(class: OrphanFeed::class, id: $saved->id ?? 0);
        self::assertInstanceOf(Relation::class, $feed->comments);

        $existing = $feed->comments->first();
        self::assertInstanceOf(PolyComment::class, $existing);
        $feed->comments->remove(item: $existing);

        (void) $this->modelsManager->save($feed);

        $count = $this->connection->count(table: 'poly_comments')
            ->where('id', 800)
            ->count();

        self::assertSame(0, $count);
    }

    public function testFlushMorphToManyPivotChangesInsertsAndDeletes(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->tags);

        $featured = null;
        $draft = null;

        foreach ($article->tags as $tag) {
            if ($tag->name === 'featured') {
                $featured = $tag;
            } elseif ($tag->name === 'draft') {
                $draft = $tag;
            }
        }

        self::assertInstanceOf(PolyTag::class, $featured);
        self::assertInstanceOf(PolyTag::class, $draft);

        $newTag = new PolyTag();
        $newTag->name = 'sticky';
        $newTag = $this->modelsManager->save($newTag);

        $article->tags->remove(item: $draft);
        $article->tags->add(item: $newTag);

        (void) $this->modelsManager->save($article);

        $stickyRow = $this->connection->count(table: 'poly_taggables')
            ->where('taggable_type', Article::class)
            ->where('taggable_id', 10)
            ->where('tag_id', $newTag->id)
            ->count();

        $draftRow = $this->connection->count(table: 'poly_taggables')
            ->where('taggable_type', Article::class)
            ->where('taggable_id', 10)
            ->where('tag_id', $draft->id)
            ->count();

        self::assertSame(1, $stickyRow);
        self::assertSame(0, $draftRow);
    }

    public function testCascadeDeleteMorphOneRemovesChild(): void
    {
        (void) $this->modelsManager->delete(
            $this->modelsManager->fetchById(class: Employee::class, id: 500),
        );

        $count = $this->connection->count(table: 'avatars')
            ->where('subject_type', Employee::class)
            ->where('subject_id', 500)
            ->count();

        self::assertSame(0, $count);
    }

    public function testMorphOneRestrictBlocksDeleteWhenChildExists(): void
    {
        $article = new RestrictArticle();
        $article->title = 'restrict-article';
        $saved = $this->modelsManager->save($article);

        $this->connection->insert(table: 'avatars')
            ->set(column: 'subject_type', value: RestrictArticle::class)
            ->set(column: 'subject_id', value: $saved->id)
            ->set(column: 'url', value: 'https://example.test/restrict.png')
            ->execute();

        try {
            (void) $this->modelsManager->delete($saved);

            self::fail('Expected RESTRICT to block delete');
        } catch (ModelException $exception) {
            self::assertStringContainsString('restrict', \strtolower($exception->getMessage()));
        }
    }

    public function testMorphManyRestrictBlocksDeleteWhenChildrenExist(): void
    {
        $article = new RestrictArticle();
        $article->title = 'restrict-article-with-comments';
        $saved = $this->modelsManager->save($article);

        self::assertNotNull($saved->id);

        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'commentable_type', value: RestrictArticle::class)
            ->set(column: 'commentable_id', value: $saved->id)
            ->set(column: 'body', value: 'blocks delete')
            ->execute();

        $rehydrated = $this->modelsManager->fetchById(
            class: RestrictArticle::class,
            id: $saved->id,
        );

        try {
            (void) $this->modelsManager->delete($rehydrated);

            self::fail('Expected RESTRICT to block delete');
        } catch (ModelException $exception) {
            self::assertStringContainsString('restrict', \strtolower($exception->getMessage()));
        }
    }

    public function testMorphOneSetNullClearsTypeAndIdOnChild(): void
    {
        $owner = new SetNullOwner();
        $owner->name = 'nullable-owner';
        $saved = $this->modelsManager->save($owner);

        $this->connection->insert(table: 'nullable_avatars')
            ->set(column: 'id', value: 700)
            ->set(column: 'subject_type', value: SetNullOwner::class)
            ->set(column: 'subject_id', value: $saved->id)
            ->set(column: 'url', value: 'https://example.test/null.png')
            ->execute();

        (void) $this->modelsManager->delete($saved);

        $row = $this->modelsManager->fetchById(class: NullableAvatar::class, id: 700);

        self::assertNull($row->subjectType);
        self::assertNull($row->subjectId);
    }

    public function testMorphManySetNullClearsTypeAndIdOnAllChildren(): void
    {
        $owner = new SetNullOwner();
        $owner->name = 'gallery-owner';
        $saved = $this->modelsManager->save($owner);

        $this->connection->insert(table: 'nullable_avatars')
            ->set(column: 'id', value: 710)
            ->set(column: 'subject_type', value: SetNullOwner::class)
            ->set(column: 'subject_id', value: $saved->id)
            ->set(column: 'url', value: 'https://example.test/g1.png')
            ->execute();
        $this->connection->insert(table: 'nullable_avatars')
            ->set(column: 'id', value: 711)
            ->set(column: 'subject_type', value: SetNullOwner::class)
            ->set(column: 'subject_id', value: $saved->id)
            ->set(column: 'url', value: 'https://example.test/g2.png')
            ->execute();

        (void) $this->modelsManager->delete($saved);

        $count = $this->connection->count(table: 'nullable_avatars')
            ->where('subject_type', SetNullOwner::class)
            ->where('subject_id', $saved->id)
            ->count();

        self::assertSame(0, $count);
    }

    public function testMorphManyBulkDeleteIssuesSingleDeleteStatement(): void
    {
        $board = new BulkDeleteBoard();
        $board->name = 'bulk-board';
        $saved = $this->modelsManager->save($board);

        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'commentable_type', value: BulkDeleteBoard::class)
            ->set(column: 'commentable_id', value: $saved->id)
            ->set(column: 'body', value: 'to bulk delete a')
            ->execute();
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'commentable_type', value: BulkDeleteBoard::class)
            ->set(column: 'commentable_id', value: $saved->id)
            ->set(column: 'body', value: 'to bulk delete b')
            ->execute();

        (void) $this->modelsManager->delete($saved);

        $count = $this->connection->count(table: 'poly_comments')
            ->where('commentable_type', BulkDeleteBoard::class)
            ->where('commentable_id', $saved->id)
            ->count();

        self::assertSame(0, $count);
    }

    public function testForceDeleteMorphSingleObjectHitsHardDeletePath(): void
    {
        (void) $this->modelsManager->forceDelete(
            $this->modelsManager->fetchById(class: Employee::class, id: 500),
        );

        $count = $this->connection->count(table: 'avatars')
            ->where('subject_type', Employee::class)
            ->where('subject_id', 500)
            ->count();

        self::assertSame(0, $count);
    }

    public function testForceDeleteMorphManyHitsHardDeleteBranchPerChild(): void
    {
        (void) $this->modelsManager->forceDelete(
            $this->modelsManager->fetchById(class: Article::class, id: 10),
        );

        $count = $this->connection->count(table: 'poly_comments')
            ->where('commentable_type', Article::class)
            ->where('commentable_id', 10)
            ->count();

        self::assertSame(0, $count);
    }

    public function testLazyMorphToReturnsNullWhenTypeMissingAndNullable(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 901)
            ->set(column: 'commentable_type', value: '')
            ->set(column: 'commentable_id', value: 0)
            ->set(column: 'body', value: 'no target')
            ->execute();

        $comment = $this->modelsManager->fetchById(class: PolyComment::class, id: 901);

        self::assertNull($comment->commentable);
    }

    public function testLazyMorphToThrowsWhenTargetRowMissing(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 902)
            ->set(column: 'commentable_type', value: Article::class)
            ->set(column: 'commentable_id', value: 9999)
            ->set(column: 'body', value: 'points at missing')
            ->execute();

        $comment = $this->modelsManager->fetchById(class: PolyComment::class, id: 902);

        $commentable = $comment->commentable;
        self::assertNotNull($commentable);

        try {
            (new \ReflectionClass($commentable))->initializeLazyObject($commentable);

            self::fail('Expected ModelException for missing target row');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'related record',
                \strtolower($exception->getMessage()),
            );
        }
    }

    public function testLazyMorphToThrowsWhenTypeMissingAndNonNullable(): void
    {
        $this->connection->insert(table: 'strict_comments')
            ->set(column: 'id', value: 903)
            ->set(column: 'commentable_type', value: '')
            ->set(column: 'commentable_id', value: 0)
            ->set(column: 'body', value: 'strict no type')
            ->execute();

        try {
            (void) $this->modelsManager->fetchById(class: StrictComment::class, id: 903);

            self::fail('Expected ModelException for missing FK on non-nullable morph');
        } catch (ModelException $exception) {
            self::assertStringContainsString('foreign key', \strtolower($exception->getMessage()));
        }
    }

    public function testLazyMorphManyEmptyForHydratedArticleWithNullId(): void
    {
        $article = $this->modelsManager->hydrator->hydrate(
            Article::class,
            ['title' => 'unsaved'],
        );

        self::assertInstanceOf(Relation::class, $article->comments);
        self::assertSame(0, $article->comments->count());
    }

    public function testLazyMorphToManyEmptyForHydratedArticleWithNullId(): void
    {
        $article = $this->modelsManager->hydrator->hydrate(
            Article::class,
            ['title' => 'unsaved'],
        );

        self::assertInstanceOf(Relation::class, $article->tags);
        self::assertSame(0, $article->tags->count());
    }

    public function testEagerMorphManyBuilderCountAndFirstStillWorkAfterEagerLoad(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'comments' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->comments);

        self::assertSame(2, $alice->comments->count());
        self::assertInstanceOf(PolyComment::class, $alice->comments->first());
    }

    public function testEagerMorphToManyBuilderCountAndFirstStillWorkAfterEagerLoad(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'tags' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->tags);

        self::assertSame(2, $alice->tags->count());
        self::assertInstanceOf(PolyTag::class, $alice->tags->first());
    }

    public function testEagerMorphManyWithLimitConstraintSlicesPrefetched(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'comments' => static fn (Relation $r): Relation => $r->page(1),
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->comments);

        $prefetchedCount = 0;

        foreach ($alice->comments as $ignored) {
            $prefetchedCount++;
        }

        self::assertSame(1, $prefetchedCount);
    }

    public function testEagerMorphToManyWithLimitConstraintSlicesPrefetched(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'tags' => static fn (Relation $r): Relation => $r->page(1),
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->tags);

        $prefetchedCount = 0;

        foreach ($alice->tags as $ignored) {
            $prefetchedCount++;
        }

        self::assertSame(1, $prefetchedCount);
    }

    public function testCreateTableEmitsCompositeIndexForMorphTo(): void
    {
        $statement = $this->modelsManager->createTable(PolyComment::class);

        self::assertInstanceOf(\Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface::class, $statement);
    }

    public function testEagerMorphOneAssignsNullWhenChildMissing(): void
    {
        $employee = new Employee();
        $employee->name = 'no-avatar';
        (void) $this->modelsManager->save($employee);

        $employees = \iterator_to_array(
            $this->modelsManager->query(Employee::class)
                ->with(
                    with: [
                        'avatar' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->where(column: 'name', value: 'no-avatar')
                ->fetchAll(),
        );

        $found = null;

        foreach ($employees as $e) {
            $found = $e;

            break;
        }

        self::assertInstanceOf(Employee::class, $found);
        self::assertNull($found->avatar);
    }

    public function testLazyMorphManyRelationHonorsWhereFilterInLoader(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->comments);

        $filtered = $article->comments->where(column: 'body', value: 'first on alice article');
        $bodies = [];

        foreach ($filtered->fetchAll() as $comment) {
            $bodies[] = $comment->body;
        }

        self::assertSame(['first on alice article'], $bodies);
    }

    public function testLazyMorphManyRelationHonorsOrderByAndLimit(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->comments);

        $shaped = $article->comments
            ->orderBy(column: 'body', direction: OrderDirection::ASC)
            ->page(1);

        $bodies = [];

        foreach ($shaped->fetchAll() as $comment) {
            $bodies[] = $comment->body;
        }

        self::assertSame(['first on alice article'], $bodies);
    }

    public function testLazyMorphManyRelationCountAppliesWhereFilter(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->comments);

        $count = $article->comments
            ->where(column: 'body', value: 'first on alice article')
            ->count();

        self::assertSame(1, $count);
    }

    public function testLazyMorphToManyRelationHonorsWhereFilterInLoader(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->tags);

        $filtered = $article->tags->where(column: 'name', value: 'featured');
        $names = [];

        foreach ($filtered->fetchAll() as $tag) {
            $names[] = $tag->name;
        }

        self::assertSame(['featured'], $names);
    }

    public function testLazyMorphToManyRelationHonorsOrderByAndLimit(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->tags);

        $shaped = $article->tags
            ->orderBy(column: 'name', direction: OrderDirection::ASC)
            ->page(1);

        $names = [];

        foreach ($shaped->fetchAll() as $tag) {
            $names[] = $tag->name;
        }

        self::assertSame(['draft'], $names);
    }

    public function testLazyMorphToManyRelationCountAppliesWhereFilter(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->tags);

        $count = $article->tags
            ->where(column: 'name', value: 'featured')
            ->count();

        self::assertSame(1, $count);
    }

    public function testEagerMorphManyRelationFetchAllReloadsWithCriteria(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'comments' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->comments);

        $filtered = $alice->comments->where(column: 'body', value: 'first on alice article');
        $bodies = [];

        foreach ($filtered->fetchAll() as $comment) {
            $bodies[] = $comment->body;
        }

        self::assertSame(['first on alice article'], $bodies);
        self::assertSame(1, $alice->comments->where(column: 'body', value: 'first on alice article')->count());
    }

    public function testEagerMorphToManyRelationFetchAllReloadsWithCriteria(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'tags' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->tags);

        $filtered = $alice->tags->where(column: 'name', value: 'draft');
        $names = [];

        foreach ($filtered->fetchAll() as $tag) {
            $names[] = $tag->name;
        }

        self::assertSame(['draft'], $names);
        self::assertSame(1, $alice->tags->where(column: 'name', value: 'draft')->count());
    }

    public function testEagerMorphManyRelationHonorsOrderByAndLimitInLoader(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'comments' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->comments);

        $shaped = $alice->comments
            ->orderBy(column: 'body', direction: OrderDirection::ASC)
            ->page(1);

        $bodies = [];

        foreach ($shaped->fetchAll() as $comment) {
            $bodies[] = $comment->body;
        }

        self::assertSame(['first on alice article'], $bodies);
    }

    public function testEagerMorphToManyRelationHonorsOrderByAndLimitInLoader(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'tags' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->tags);

        $shaped = $alice->tags
            ->orderBy(column: 'name', direction: OrderDirection::ASC)
            ->page(1);

        $names = [];

        foreach ($shaped->fetchAll() as $tag) {
            $names[] = $tag->name;
        }

        self::assertSame(['draft'], $names);
    }

    public function testCascadeSaveMorphToWithNullValueSkipsCleanly(): void
    {
        $comment = new CascadeSaveComment();
        $comment->body = 'null commentable';
        $comment->commentableType = 'placeholder';
        $comment->commentableId = 1;

        $savedComment = $this->modelsManager->save($comment);

        self::assertNotNull($savedComment->id);
        self::assertSame('placeholder', $savedComment->commentableType);
    }

    public function testEagerMorphToThrowsForUnresolvableTypeDuringBatch(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 910)
            ->set(column: 'commentable_type', value: 'App\\Model\\NopeType')
            ->set(column: 'commentable_id', value: 1)
            ->set(column: 'body', value: 'eager unresolvable')
            ->execute();

        try {
            \iterator_to_array(
                $this->modelsManager->query(PolyComment::class)
                    ->with(
                        with: [
                            'commentable' => static fn (Relation $r): Relation => $r,
                        ],
                    )
                    ->where(column: 'id', value: 910)
                    ->fetchAll(),
            );

            self::fail('Expected ModelException for unresolvable eager type');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'cannot resolve type value',
                $exception->getMessage(),
            );
        }
    }

    public function testForceDeleteMorphOneRelationHitsHardDeletePath(): void
    {
        $employee = $this->modelsManager->fetchById(class: Employee::class, id: 500);

        (void) $this->modelsManager->forceDelete($employee);

        $count = $this->connection->count(table: 'avatars')
            ->where('subject_type', Employee::class)
            ->where('subject_id', 500)
            ->count();

        self::assertSame(0, $count);
    }

    public function testCascadeSaveWithForceMaterializeInitializesLazyMorphTo(): void
    {
        $sourceArticle = new Article();
        $sourceArticle->title = 'origin';
        $sourceArticle->userId = 1;
        $sourceArticle = $this->modelsManager->save($sourceArticle);

        $lazyComment = new CascadeSaveComment();
        $lazyComment->body = 'lazy-force';
        $lazyComment->commentableType = Article::class;
        $lazyComment->commentableId = $sourceArticle->id ?? 0;
        $lazyComment = $this->modelsManager->save($lazyComment);

        $rehydrated = $this->modelsManager->fetchById(
            class: CascadeSaveComment::class,
            id: $lazyComment->id ?? 0,
        );

        (void) $this->modelsManager->save(
            model: $rehydrated,
            forceMaterialize: true,
        );

        self::assertSame(Article::class, $rehydrated->commentableType);
    }

    public function testCascadeSaveMorphManyRelationWithForceMaterializeIteratesUntouchedRelation(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        (void) $this->modelsManager->save(
            model: $article,
            forceMaterialize: true,
        );

        $count = $this->connection->count(table: 'poly_comments')
            ->where('commentable_type', Article::class)
            ->where('commentable_id', 10)
            ->count();

        self::assertSame(2, $count);
    }

    public function testEagerMorphToSkipsParentsWithEmptyTypeInBatch(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 920)
            ->set(column: 'commentable_type', value: '')
            ->set(column: 'commentable_id', value: 0)
            ->set(column: 'body', value: 'empty type in batch')
            ->execute();

        $comments = \iterator_to_array(
            $this->modelsManager->query(PolyComment::class)
                ->with(
                    with: [
                        'commentable' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->where(column: 'id', value: 920)
                ->fetchAll(),
        );

        $target = null;

        foreach ($comments as $comment) {
            $target = $comment;

            break;
        }

        self::assertInstanceOf(PolyComment::class, $target);
        self::assertNull($target->commentable);
    }

    public function testEagerMorphToAssignsNullWhenTargetRowMissingAndPropertyIsNullable(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 921)
            ->set(column: 'commentable_type', value: Article::class)
            ->set(column: 'commentable_id', value: 99999)
            ->set(column: 'body', value: 'points at missing (eager)')
            ->execute();

        $comments = \iterator_to_array(
            $this->modelsManager->query(PolyComment::class)
                ->with(
                    with: [
                        'commentable' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->where(column: 'id', value: 921)
                ->fetchAll(),
        );

        $target = null;

        foreach ($comments as $comment) {
            $target = $comment;

            break;
        }

        self::assertInstanceOf(PolyComment::class, $target);
        self::assertNull($target->commentable);
    }

    public function testEagerMorphToThrowsForMissingTargetWhenPropertyNonNullable(): void
    {
        $this->connection->insert(table: 'strict_comments')
            ->set(column: 'id', value: 922)
            ->set(column: 'commentable_type', value: Article::class)
            ->set(column: 'commentable_id', value: 99998)
            ->set(column: 'body', value: 'strict missing (eager)')
            ->execute();

        try {
            \iterator_to_array(
                $this->modelsManager->query(StrictComment::class)
                    ->with(
                        with: [
                            'commentable' => static fn (Relation $r): Relation => $r,
                        ],
                    )
                    ->where(column: 'id', value: 922)
                    ->fetchAll(),
            );

            self::fail('Expected ModelException for missing target during eager on non-nullable morph');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'related record',
                \strtolower($exception->getMessage()),
            );
        }
    }

    public function testCascadeSaveMorphToWithLazyProxySkipsWithoutForceMaterialize(): void
    {
        $this->connection->insert(table: 'cascade_save_comments')
            ->set(column: 'id', value: 930)
            ->set(column: 'commentable_type', value: Article::class)
            ->set(column: 'commentable_id', value: 10)
            ->set(column: 'body', value: 'lazy skip')
            ->execute();

        $comment = $this->modelsManager->fetchById(class: CascadeSaveComment::class, id: 930);

        (void) $this->modelsManager->save(
            model: $comment,
            skipValidation: true,
        );

        self::assertSame('lazy skip', $comment->body);
    }

    public function testCascadeSaveMorphOneWithLazyProxySkipsWithoutForceMaterialize(): void
    {
        $poster = new Poster();
        $poster->name = 'lazy-skip-poster';
        $saved = $this->modelsManager->save($poster);

        self::assertNotNull($saved->id);

        $this->connection->insert(table: 'plain_avatars')
            ->set(column: 'subject_type', value: Poster::class)
            ->set(column: 'subject_id', value: $saved->id)
            ->set(column: 'url', value: 'https://example.test/lazyskip.png')
            ->execute();

        $rehydrated = $this->modelsManager->fetchById(class: Poster::class, id: $saved->id);

        (void) $this->modelsManager->save(
            model: $rehydrated,
            skipValidation: true,
        );

        self::assertSame('lazy-skip-poster', $rehydrated->name);
    }

    public function testCascadeSaveMorphOneWithLazyProxyAndForceMaterializeInitializesAndSaves(): void
    {
        $poster = new Poster();
        $poster->name = 'lazy-force-poster';
        $saved = $this->modelsManager->save($poster);

        self::assertNotNull($saved->id);

        $this->connection->insert(table: 'plain_avatars')
            ->set(column: 'subject_type', value: Poster::class)
            ->set(column: 'subject_id', value: $saved->id)
            ->set(column: 'url', value: 'https://example.test/lazyforce.png')
            ->execute();

        $rehydrated = $this->modelsManager->fetchById(class: Poster::class, id: $saved->id);

        (void) $this->modelsManager->save(
            model: $rehydrated,
            forceMaterialize: true,
            skipValidation: true,
        );

        $count = $this->connection->count(table: 'plain_avatars')
            ->where('subject_type', Poster::class)
            ->where('subject_id', $saved->id)
            ->count();

        self::assertSame(1, $count);
    }

    public function testCascadeDeleteMorphOneNoOpsWhenChildRowMissing(): void
    {
        $poster = new Poster();
        $poster->name = 'no-child-poster';
        $saved = $this->modelsManager->save($poster);

        self::assertNotNull($saved->id);

        (void) $this->modelsManager->delete($saved);

        $count = $this->connection->count(table: 'posters')
            ->where('id', $saved->id)
            ->count();

        self::assertSame(0, $count);
    }

    public function testMorphOneRestrictAllowsDeleteWhenNoChildExists(): void
    {
        $article = new RestrictArticle();
        $article->title = 'restrict-no-children';
        $saved = $this->modelsManager->save($article);

        self::assertNotNull($saved->id);

        $rehydrated = $this->modelsManager->fetchById(
            class: RestrictArticle::class,
            id: $saved->id,
        );

        (void) $this->modelsManager->delete($rehydrated);

        $count = $this->connection->count(table: 'restrict_articles')
            ->where('id', $saved->id)
            ->count();

        self::assertSame(0, $count);
    }

    public function testCascadeSaveMorphManyEarlyReturnsForUnmaterializedRelation(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 11);

        (void) $this->modelsManager->save($article);

        $count = $this->connection->count(table: 'poly_comments')
            ->where('commentable_type', Article::class)
            ->where('commentable_id', 11)
            ->count();

        self::assertSame(1, $count);
    }

    public function testCascadeSaveMorphToManyEarlyReturnsForUnmaterializedPivot(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 11);

        (void) $this->modelsManager->save($article);

        $count = $this->connection->count(table: 'poly_taggables')
            ->where('taggable_type', Article::class)
            ->where('taggable_id', 11)
            ->count();

        self::assertSame(1, $count);
    }

    public function testLazyMorphOneSetsNullWhenSourceIsNullAndPropertyIsNullable(): void
    {
        $employee = $this->modelsManager->hydrator->hydrate(
            Employee::class,
            ['name' => 'no-id'],
        );

        self::assertNull($employee->avatar);
    }

    public function testLazyMorphOneThrowsWhenSourceIsNullAndPropertyIsNonNullable(): void
    {
        try {
            $this->modelsManager->hydrator->hydrate(
                StrictPoster::class,
                ['name' => 'strict-no-id'],
            );

            self::fail('Expected ModelException for missing FK on non-nullable MorphOne');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'foreign key',
                \strtolower($exception->getMessage()),
            );
        }
    }

    public function testLazyMorphOneThrowsWhenLazyProxyResolvesToMissingRow(): void
    {
        $this->connection->insert(table: 'employees')
            ->set(column: 'id', value: 555)
            ->set(column: 'name', value: 'no-avatar-employee')
            ->execute();

        $employee = $this->modelsManager->fetchById(class: Employee::class, id: 555);

        $avatar = $employee->avatar;
        self::assertNotNull($avatar);

        try {
            (new \ReflectionClass($avatar))->initializeLazyObject($avatar);

            self::fail('Expected ModelException for missing related MorphOne record');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'related record',
                \strtolower($exception->getMessage()),
            );
        }
    }

    public function testLazyMorphManyTotalCountAppliesCriteriaViaCountBuilder(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->comments);

        $filtered = $article->comments->where(column: 'body', value: 'first on alice article');

        self::assertSame(1, $filtered->totalCount);
    }

    public function testLazyMorphToManyTotalCountAppliesCriteriaViaCountBuilder(): void
    {
        $article = $this->modelsManager->fetchById(class: Article::class, id: 10);

        self::assertInstanceOf(Relation::class, $article->tags);

        $filtered = $article->tags->where(column: 'name', value: 'featured');

        self::assertSame(1, $filtered->totalCount);
    }

    public function testLazyMorphOneWithExplicitLocalKeyResolvesTarget(): void
    {
        $this->connection->insert(table: 'localkey_posters')
            ->set(column: 'id', value: 700)
            ->set(column: 'external_id', value: 4242)
            ->set(column: 'name', value: 'localkey-poster')
            ->execute();

        $this->connection->insert(table: 'plain_avatars')
            ->set(column: 'subject_type', value: LocalKeyPoster::class)
            ->set(column: 'subject_id', value: 4242)
            ->set(column: 'url', value: 'https://example.test/localkey.png')
            ->execute();

        $poster = $this->modelsManager->fetchById(class: LocalKeyPoster::class, id: 700);

        self::assertInstanceOf(PlainAvatar::class, $poster->badge);
        self::assertSame('https://example.test/localkey.png', $poster->badge->url);
    }

    public function testNestedEagerMorphToSkipsRecursionIntoNullCommentable(): void
    {
        $this->connection->insert(table: 'poly_comments')
            ->set(column: 'id', value: 970)
            ->set(column: 'commentable_type', value: Article::class)
            ->set(column: 'commentable_id', value: 88888)
            ->set(column: 'body', value: 'nested-with-null-parent')
            ->execute();

        $comments = \iterator_to_array(
            $this->modelsManager->query(PolyComment::class)
                ->with(
                    with: [
                        'commentable.author' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->where(column: 'id', value: 970)
                ->fetchAll(),
        );

        $target = null;

        foreach ($comments as $comment) {
            $target = $comment;

            break;
        }

        self::assertInstanceOf(PolyComment::class, $target);
        self::assertNull($target->commentable);
    }

    public function testEagerMorphOneAssignsResolvedAvatarForBatchOfEmployees(): void
    {
        $employees = \iterator_to_array(
            $this->modelsManager->query(Employee::class)
                ->with(
                    with: [
                        'avatar' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($employees as $employee) {
            if ($employee->id === 500) {
                $alice = $employee;

                break;
            }
        }

        self::assertInstanceOf(Employee::class, $alice);
        self::assertInstanceOf(Avatar::class, $alice->avatar);
        self::assertSame('https://example.test/alice.png', $alice->avatar->url);
    }

    public function testEagerMorphManySkipsParentWithNullPrimaryKeyInBatch(): void
    {
        $unsavedArticle = $this->modelsManager->hydrator->hydrate(
            Article::class,
            ['title' => 'unsaved-in-batch'],
        );

        $realArticle = $this->modelsManager->fetchById(class: Article::class, id: 10);

        $this->modelsManager->hydrator->eagerLoad(
            parents: [$unsavedArticle, $realArticle],
            with: [
                'comments' => static fn (Relation $r): Relation => $r,
            ],
        );

        self::assertInstanceOf(Relation::class, $realArticle->comments);
        self::assertInstanceOf(Relation::class, $unsavedArticle->comments);
        self::assertSame(2, $realArticle->comments->totalCount);
    }

    public function testEagerLoadMorphToThrowsForUnresolvableTypeInBatchDispatch(): void
    {
        $comment = new PolyComment();
        $comment->id = 981;
        $comment->commentableType = 'App\\Model\\NoSuch';
        $comment->commentableId = 1;
        $comment->body = 'direct eager unresolvable';

        try {
            $this->modelsManager->hydrator->eagerLoad(
                parents: [$comment],
                with: [
                    'commentable' => static fn (Relation $r): Relation => $r,
                ],
            );

            self::fail('Expected ModelException for unresolvable morph type in eager batch');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'cannot resolve type value',
                $exception->getMessage(),
            );
        }
    }

    public function testEagerLoadMorphToThrowsWhenTypeIsMissingAndPropertyNonNullable(): void
    {
        $comment = new StrictComment();
        $comment->id = 982;
        $comment->commentableType = '';
        $comment->commentableId = 0;
        $comment->body = 'direct eager missing FK non-nullable';

        try {
            $this->modelsManager->hydrator->eagerLoad(
                parents: [$comment],
                with: [
                    'commentable' => static fn (Relation $r): Relation => $r,
                ],
            );

            self::fail('Expected ModelException for missing morph FK on non-nullable property during eager');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'foreign key',
                \strtolower($exception->getMessage()),
            );
        }
    }

    public function testEagerLoadMorphOneSkipsParentWithNullSourceKey(): void
    {
        $unsavedEmployee = $this->modelsManager->hydrator->hydrate(
            Employee::class,
            ['name' => 'unsaved-in-morph-one-batch'],
        );

        $realEmployee = $this->modelsManager->fetchById(class: Employee::class, id: 500);

        $this->modelsManager->hydrator->eagerLoad(
            parents: [$unsavedEmployee, $realEmployee],
            with: [
                'avatar' => static fn (Relation $r): Relation => $r,
            ],
        );

        self::assertInstanceOf(Avatar::class, $realEmployee->avatar);
        self::assertNull($unsavedEmployee->avatar);
    }

    public function testEagerMorphManyTotalCountReadsCountBuilderAfterEager(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'comments' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->comments);

        $filtered = $alice->comments->where(column: 'body', value: 'first on alice article');

        self::assertSame(1, $filtered->totalCount);
    }

    public function testEagerMorphToManyTotalCountReadsCountBuilderAfterEager(): void
    {
        $articles = \iterator_to_array(
            $this->modelsManager->query(Article::class)
                ->with(
                    with: [
                        'tags' => static fn (Relation $r): Relation => $r,
                    ],
                )
                ->fetchAll(),
        );

        $alice = null;

        foreach ($articles as $article) {
            if ($article->id === 10) {
                $alice = $article;

                break;
            }
        }

        self::assertInstanceOf(Article::class, $alice);
        self::assertInstanceOf(Relation::class, $alice->tags);

        $filtered = $alice->tags->where(column: 'name', value: 'draft');

        self::assertSame(1, $filtered->totalCount);
    }

    public function testCascadeSaveMorphToWithLazyProxyAndForceMaterializeInitializesTarget(): void
    {
        $host = new SimpleHost();
        $host->name = 'lazy-force-host';
        $savedHost = $this->modelsManager->save($host);

        self::assertNotNull($savedHost->id);

        $this->connection->insert(table: 'hosted_comments')
            ->set(column: 'id', value: 940)
            ->set(column: 'host_type', value: SimpleHost::class)
            ->set(column: 'host_id', value: $savedHost->id)
            ->set(column: 'body', value: 'force-materialize morph-to')
            ->execute();

        $rehydrated = $this->modelsManager->fetchById(class: HostedComment::class, id: 940);

        (void) $this->modelsManager->save(
            model: $rehydrated,
            forceMaterialize: true,
            skipValidation: true,
        );

        $count = $this->connection->count(table: 'simple_hosts')
            ->where('id', $savedHost->id)
            ->count();

        self::assertSame(1, $count);
        self::assertSame(SimpleHost::class, $rehydrated->hostType);
    }
}
