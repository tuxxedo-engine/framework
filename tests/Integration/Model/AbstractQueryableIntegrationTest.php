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

use Fixture\Model\Country;
use Fixture\Model\Post;
use Fixture\Model\User;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

class AbstractQueryableIntegrationTest extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCountriesTable();
        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();
        $this->createCommentsTable();
        $this->createTagsTable();
        $this->createPostTagPivot();

        $this->connection->query(
            sql: "INSERT INTO countries (id, name, code) VALUES (1, 'Sweden', 'SE')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (1, 'Alice', 'alice@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (id, name, email, isActive, postCount, score, country_id) VALUES (2, 'Bob', 'bob@example.test', 1, 0, 0.0, 1)",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, publishedAt, viewCount, rating) VALUES (10, 1, 'First',  '', 'published', '2026-01-01', 100, '4.50')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, publishedAt, viewCount, rating) VALUES (11, 1, 'Second', '', 'draft',      NULL,         50,  '3.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, publishedAt, viewCount, rating) VALUES (12, 1, 'Third',  '', 'published', '2026-01-05', 200, '5.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (id, user_id, title, body, status, publishedAt, viewCount, rating) VALUES (13, 1, 'Fourth', '', 'archived',  '2026-01-03', 10,  '2.00')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO comments (id, post_id, user_id, body) VALUES (100, 10, 2, 'nice')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO tags (id, slug, name) VALUES (200, 'php', 'PHP')",
            native: true,
        );

        $this->connection->query(
            sql: 'INSERT INTO post_tag (post_id, tag_id) VALUES (10, 200)',
            native: true,
        );
    }

    /**
     * @return Relation<Post>
     */
    private function alicePosts(): Relation
    {
        $user = $this->modelsManager->fetchByIdentifier(
            class: User::class,
            id: 1,
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );

        return $user->posts;
    }

    public function testOffsetGetReturnsMaterializedItemByIndex(): void
    {
        $posts = $this->alicePosts()->orderBy(
            column: 'id',
        );

        self::assertInstanceOf(
            Post::class,
            $posts[0],
        );

        self::assertSame(
            10,
            $posts[0]->id,
        );
    }

    public function testOffsetExistsReturnsTrueForMaterializedIndex(): void
    {
        $posts = $this->alicePosts()->orderBy(
            column: 'id',
        );

        self::assertTrue(
            isset($posts[0]),
        );

        self::assertFalse(
            isset($posts[99]),
        );
    }

    public function testOffsetSetThrowsImmutableException(): void
    {
        $posts = $this->alicePosts();

        $this->expectException(ModelException::class);

        $posts[0] = new Post();
    }

    public function testOffsetUnsetThrowsImmutableException(): void
    {
        $posts = $this->alicePosts();

        $this->expectException(ModelException::class);

        unset($posts[0]);
    }

    public function testWhereFiltersByEqualityOperator(): void
    {
        $filtered = $this->alicePosts()->where(
            column: 'status',
            value: 'published',
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereWithExplicitOperator(): void
    {
        $filtered = $this->alicePosts()->where(
            column: 'viewCount',
            value: 50,
            operator: ConditionOperator::GREATER_THAN,
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereBroadensMatch(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'published',
            )
            ->orWhere(
                column: 'status',
                value: 'archived',
            );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testWhereNullFiltersUnpublishedPosts(): void
    {
        $filtered = $this->alicePosts()->whereNull(
            column: 'publishedAt',
        );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testWhereNotNullFiltersPublishedPosts(): void
    {
        $filtered = $this->alicePosts()->whereNotNull(
            column: 'publishedAt',
        );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testOrWhereNullAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'archived',
            )
            ->orWhereNull(
                column: 'publishedAt',
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereNotNullAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'archived',
            )
            ->orWhereNotNull(
                column: 'publishedAt',
            );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testWhereInFiltersByIdSet(): void
    {
        $filtered = $this->alicePosts()->whereIn(
            column: 'id',
            values: [
                10,
                12,
            ],
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereNotInFiltersByComplementSet(): void
    {
        $filtered = $this->alicePosts()->whereNotIn(
            column: 'status',
            values: [
                'draft',
            ],
        );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testOrWhereInAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereIn(
                column: 'id',
                values: [
                    12,
                ],
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereNotInAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'archived',
            )
            ->orWhereNotIn(
                column: 'id',
                values: [
                    10,
                    11,
                    12,
                    13,
                ],
            );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testWhereBetweenFiltersRange(): void
    {
        $filtered = $this->alicePosts()->whereBetween(
            column: 'viewCount',
            from: 20,
            to: 150,
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereNotBetweenFiltersOutsideRange(): void
    {
        $filtered = $this->alicePosts()->whereNotBetween(
            column: 'viewCount',
            from: 20,
            to: 150,
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereBetweenAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereBetween(
                column: 'viewCount',
                from: 150,
                to: 300,
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereNotBetweenAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereNotBetween(
                column: 'viewCount',
                from: 20,
                to: 250,
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereColumnComparesColumnsOnSameRow(): void
    {
        $filtered = $this->alicePosts()->whereColumn(
            column: 'viewCount',
            other: 'user_id',
            operator: ConditionOperator::GREATER_THAN,
        );

        self::assertSame(
            4,
            $filtered->count(),
        );
    }

    public function testOrWhereColumnAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereColumn(
                column: 'viewCount',
                other: 'user_id',
                operator: ConditionOperator::LESS_THAN,
            );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testWhereRawEmitsCustomFragment(): void
    {
        $filtered = $this->alicePosts()->whereRaw(
            sql: 'viewCount >= 100',
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereLikeMatchesPattern(): void
    {
        $filtered = $this->alicePosts()->whereLike(
            column: 'title',
            pattern: 'F%',
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereNotLikeExcludesPattern(): void
    {
        $filtered = $this->alicePosts()->whereNotLike(
            column: 'title',
            pattern: 'F%',
        );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereLikeAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereLike(
                column: 'title',
                pattern: 'Th%',
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testOrWhereNotLikeAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereNotLike(
                column: 'title',
                pattern: 'F%',
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testWhereNotNegates(): void
    {
        $filtered = $this->alicePosts()->whereNot(
            callback: static function (WhereStatementInterface $statement): void {
                $statement->where(
                    column: 'status',
                    value: 'draft',
                );
            },
        );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testOrWhereNotAsAlternate(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'id',
                value: 10,
            )
            ->orWhereNot(
                callback: static function (WhereStatementInterface $statement): void {
                    $statement->where(
                        column: 'status',
                        value: 'draft',
                    );
                },
            );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testWhereGroupWrapsConditions(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'user_id',
                value: 1,
            )
            ->whereGroup(
                callback: static function (WhereStatementInterface $statement): void {
                    $statement
                        ->where(
                            column: 'status',
                            value: 'published',
                        )
                        ->orWhere(
                            column: 'status',
                            value: 'archived',
                        );
                },
            );

        self::assertSame(
            3,
            $filtered->count(),
        );
    }

    public function testOrWhereGroupWrapsAlternateConditions(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereGroup(
                callback: static function (WhereStatementInterface $statement): void {
                    $statement
                        ->where(
                            column: 'viewCount',
                            value: 150,
                            operator: ConditionOperator::GREATER_THAN,
                        )
                        ->where(
                            column: 'status',
                            value: 'published',
                        );
                },
            );

        self::assertSame(
            2,
            $filtered->count(),
        );
    }

    public function testInnerJoinRestrictsToMatchingUsers(): void
    {
        $joined = $this->alicePosts()->innerJoin(
            table: 'users',
            first: 'posts.user_id',
            second: 'users.id',
        );

        self::assertSame(
            4,
            $joined->count(),
        );
    }

    public function testLeftJoinKeepsPosts(): void
    {
        $joined = $this->alicePosts()->leftJoin(
            table: 'users',
            first: 'posts.user_id',
            second: 'users.id',
        );

        self::assertSame(
            4,
            $joined->count(),
        );
    }

    public function testRightJoinYieldsExpectedRowCount(): void
    {
        $joined = $this->alicePosts()->rightJoin(
            table: 'users',
            first: 'posts.user_id',
            second: 'users.id',
        );

        self::assertGreaterThanOrEqual(
            4,
            $joined->count(),
        );
    }

    public function testCrossJoinMultipliesRows(): void
    {
        $joined = $this->alicePosts()->crossJoin(
            table: 'users',
        );

        self::assertSame(
            8,
            $joined->count(),
        );
    }

    public function testPageAppliesLimitAndOffset(): void
    {
        $paged = $this->alicePosts()
            ->orderBy(
                column: 'id',
            )
            ->page(
                limit: 2,
                offset: 2,
            );

        $items = \iterator_to_array(
            $paged->fetchAll(),
            preserve_keys: false,
        );

        self::assertCount(
            2,
            $items,
        );

        self::assertSame(
            12,
            $items[0]->id,
        );
    }

    public function testSliceAppliesLimitAndOffset(): void
    {
        $sliced = $this->alicePosts()
            ->orderBy(
                column: 'id',
            )
            ->slice(
                limit: 2,
                offset: 1,
            );

        $items = [];

        foreach ($sliced as $item) {
            $items[] = $item;
        }

        self::assertCount(
            2,
            $items,
        );

        self::assertSame(
            11,
            $items[0]->id,
        );
    }

    /**
     * @return SelectStatementInterface
     */
    private function existingUserSubquery(int $userId): SelectStatementInterface
    {
        return $this->connection->select(
            table: 'users',
        )
            ->select('1')
            ->where(
                column: 'users.id',
                value: $userId,
            );
    }

    public function testWhereExistsPassesRowsWhenSubqueryHasResults(): void
    {
        $filtered = $this->alicePosts()->whereExists(
            subquery: $this->existingUserSubquery(
                userId: 1,
            ),
        );

        self::assertSame(
            4,
            $filtered->count(),
        );
    }

    public function testWhereNotExistsFiltersOutRowsWhenSubqueryHasResults(): void
    {
        $filtered = $this->alicePosts()->whereNotExists(
            subquery: $this->existingUserSubquery(
                userId: 1,
            ),
        );

        self::assertSame(
            0,
            $filtered->count(),
        );
    }

    public function testOrWhereExistsBroadensMatch(): void
    {
        $filtered = $this->alicePosts()
            ->where(column: 'status', value: 'draft')
            ->orWhereExists(
                subquery: $this->existingUserSubquery(
                    userId: 1,
                ),
            );

        self::assertSame(
            4,
            $filtered->count(),
        );
    }

    public function testOrWhereNotExistsBroadensMatch(): void
    {
        $filtered = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'draft',
            )
            ->orWhereNotExists(
                subquery: $this->existingUserSubquery(
                    userId: 999,
                ),
            );

        self::assertSame(
            4,
            $filtered->count(),
        );
    }

    public function testWhereHasFiltersByRelationExistence(): void
    {
        $filtered = $this->alicePosts()->whereHas(
            relationName: 'author',
        );

        self::assertSame(
            4,
            $filtered->count(),
        );
    }

    public function testWhereHasWithConstraintClosure(): void
    {
        $filtered = $this->alicePosts()->whereHas(
            relationName: 'author',
            callback: static function (SelectStatementInterface $statement): void {
                $statement->where(
                    column: 'name',
                    value: 'Bob',
                );
            },
        );

        self::assertSame(
            0,
            $filtered->count(),
        );
    }

    public function testWhereDoesntHaveExcludesRowsWhoseRelationExists(): void
    {
        $filtered = $this->alicePosts()->whereDoesntHave(
            relationName: 'author',
        );

        self::assertSame(
            0,
            $filtered->count(),
        );
    }

    public function testFirstReturnsNullWhenBuilderYieldsEmpty(): void
    {
        $result = $this->alicePosts()
            ->where(
                column: 'status',
                value: 'nonexistent-status',
            )
            ->first();

        self::assertNull(
            $result,
        );
    }

    public function testWhereHasHasManyRelationBuildsSubquery(): void
    {
        $filtered = $this->alicePosts()->whereHas(
            relationName: 'comments',
        );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testWhereHasBelongsToManyRelationBuildsSubquery(): void
    {
        $filtered = $this->alicePosts()->whereHas(
            relationName: 'tags',
        );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testWhereHasHasManyThroughRelationBuildsSubquery(): void
    {
        $filtered = $this->modelsManager->query(class: Country::class)
            ->whereHas(
                relationName: 'posts',
            );

        self::assertSame(
            1,
            $filtered->count(),
        );
    }

    public function testWhereHasThrowsForUnknownRelationName(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->alicePosts()->whereHas(
            relationName: 'nonExistent',
        );
    }

    public function testWhereHasThrowsWhenRelationLacksModelContext(): void
    {
        $orphan = Relation::createFromPrefetched(
            values: [],
        );

        $this->expectException(ModelException::class);

        (void) $orphan->whereHas(
            relationName: 'anything',
        );
    }
}
