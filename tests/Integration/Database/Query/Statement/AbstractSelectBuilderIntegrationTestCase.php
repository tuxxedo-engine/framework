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

namespace Integration\Database\Query\Statement;

use Fixture\Database\HydratableTestUser;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\Query\Statement\SelectStatement;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\SqlException;

abstract class AbstractSelectBuilderIntegrationTestCase extends AbstractWhereClauseBuilderIntegrationTestCase
{
    protected function runWhereMatch(
        \Closure $configureBuilder,
    ): bool {
        $builder = $this->connection->select(
            table: 'users',
        );

        $configureBuilder($builder);

        /** @var SelectStatement $builder */
        return $builder->execute()->count() > 0;
    }

    public function testSelectDefaultsToStar(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testSelectExplicitColumns(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $row = $this->connection->select(
            table: 'users',
        )
            ->select('name', 'email')
            ->execute()
            ->fetchAssoc();

        self::assertArrayHasKey(
            'name',
            $row,
        );

        self::assertArrayHasKey(
            'email',
            $row,
        );

        self::assertArrayNotHasKey(
            'id',
            $row,
        );
    }

    public function testSelectAggregateExpression(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $row = $this->connection->select(
            table: 'users',
        )
            ->select('COUNT(*)')
            ->execute()
            ->fetchRow();

        self::assertEquals(
            3,
            $row[0],
        );
    }

    public function testSelectDistinctFoldsDuplicates(): void
    {
        $this->createUsersSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a1@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a2@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Bob', 'b@example.test')",
            native: true,
        );

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->distinct()
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testOrderByAscendingByDefault(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $names = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->orderBy(
                    column: 'name',
                )
                ->execute() as $row
        ) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $names,
        );
    }

    public function testOrderByDescendingViaEnum(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $names = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->orderBy(
                    column: 'name',
                    direction: OrderDirection::DESC,
                )
                ->execute() as $row
        ) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Charlie',
                'Bob',
                'Alice',
            ],
            $names,
        );
    }

    public function testOrderByDescendingViaString(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $names = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->orderBy(
                    column: 'name',
                    direction: 'DESC',
                )
                ->execute() as $row
        ) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Charlie',
                'Bob',
                'Alice',
            ],
            $names,
        );
    }

    public function testOrderByMultipleColumns(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->orderBy(
                column: 'name',
                direction: OrderDirection::DESC,
            )
            ->orderBy(
                column: 'id',
                direction: OrderDirection::ASC,
            )
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testOrderByPutsNullsLastAscending(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->orderBy(
                        column: 'email',
                    ),
            ),
        );
    }

    public function testOrderByPutsNullsLastDescending(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Bob',
                'Alice',
                'Charlie',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->orderBy(
                        column: 'email',
                        direction: OrderDirection::DESC,
                    ),
            ),
        );
    }

    public function testOrderBySubqueryOrdersByCorrelatedValue(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Bob',
                'Alice',
                'Charlie',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->orderBySubquery(
                        subquery: $this->postCount(),
                        direction: OrderDirection::DESC,
                    )
                    ->orderBy(
                        column: 'id',
                    ),
            ),
        );
    }

    public function testOrderBySameColumnTwiceKeepsLastDirection(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Charlie',
                'Bob',
                'Alice',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->orderBy(
                        column: 'name',
                    )
                    ->orderBy(
                        column: 'name',
                        direction: OrderDirection::DESC,
                    ),
            ),
        );
    }

    public function testOrderBySubqueryAcceptsStringDirection(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Bob',
                'Alice',
                'Charlie',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->orderBySubquery(
                        subquery: $this->postCount(),
                        direction: 'desc',
                    ),
            ),
        );
    }

    public function testOrderBySubqueryKeepsCallOrderWithPlainColumns(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Charlie',
                'Bob',
                'Alice',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->orderBy(
                        column: 'name',
                        direction: OrderDirection::DESC,
                    )
                    ->orderBySubquery(
                        subquery: $this->postCount(),
                    ),
            ),
        );
    }

    public function testNestedSubqueryParametersDoNotCollide(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $anyBobPost = $this->connection->select(
            table: 'posts',
        )
            ->where(
                column: 'posts.title',
                value: 'Post by Bob',
            );

        $alicesPost = $this->connection->select(
            table: 'posts',
        )
            ->where(
                column: 'posts.title',
                value: 'Post by Alice',
            )
            ->whereColumn(
                column: 'posts.user_id',
                other: 'users.id',
            )
            ->whereExists($anyBobPost);

        self::assertSame(
            [
                'Alice',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->whereExists($alicesPost),
            ),
        );
    }

    public function testWhereSubqueryComparesSubqueryToValue(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->whereSubquery(
                        subquery: $this->postCount(),
                        value: 2,
                        operator: '>=',
                    ),
            ),
        );
    }

    public function testOrWhereSubqueryCombinesWithOr(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Bob',
                'Charlie',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->where(
                        column: 'name',
                        value: 'Charlie',
                    )
                    ->orWhereSubquery(
                        subquery: $this->postCount(),
                        value: 2,
                    )
                    ->orderBy(
                        column: 'name',
                    ),
            ),
        );
    }

    public function testWhereSubqueryRejectsListOperator(): void
    {
        $this->expectException(SqlException::class);

        $this->connection->select(
            table: 'users',
        )
            ->whereSubquery(
                subquery: $this->postCount(),
                value: 2,
                operator: ConditionOperator::IN,
            );
    }

    public function testGroupBySingleColumn(): void
    {
        $this->createUsersSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a1@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a2@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Bob', 'b@example.test')",
            native: true,
        );

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testGroupByMultipleColumns(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name', 'email')
            ->groupBy('name', 'email')
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testGroupByAndOrderByQualifiedColumnsAcrossJoin(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $names = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->select('users.name')
                ->innerJoin(
                    table: 'posts',
                    first: 'users.id',
                    second: 'posts.user_id',
                )
                ->groupBy('users.name')
                ->orderBy(
                    column: 'users.name',
                    direction: OrderDirection::DESC,
                )
                ->execute() as $row
        ) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Bob',
                'Alice',
            ],
            $names,
        );
    }

    public function testOrderByQualifiedColumnAcrossJoin(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $titles = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->select('posts.title')
                ->innerJoin(
                    table: 'posts',
                    first: 'users.id',
                    second: 'posts.user_id',
                )
                ->orderBy(
                    column: 'posts.title',
                )
                ->execute() as $row
        ) {
            $titles[] = $row->properties['title'];
        }

        self::assertSame(
            [
                'Another Bob post',
                'Post by Alice',
                'Post by Bob',
            ],
            $titles,
        );
    }

    public function testGroupByWithHaving(): void
    {
        $this->createUsersSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a1@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a2@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Bob', 'b@example.test')",
            native: true,
        );

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name', 'COUNT(*)')
            ->groupBy('name')
            ->having(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testHavingWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->having(
                column: 'name',
                value: 'Bob',
                operator: '!=',
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testOrHaving(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->having(
                column: 'name',
                value: 'nobody',
            )
            ->orHaving(
                column: 'name',
                value: 'Alice',
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testHavingIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->havingIn(
                column: 'name',
                values: [
                    'Alice',
                    'Bob',
                ],
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testHavingNotIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->havingNotIn(
                column: 'name',
                values: [
                    'Alice',
                ],
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testOrHavingIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->having(
                column: 'name',
                value: 'nobody',
            )
            ->orHavingIn(
                column: 'name',
                values: [
                    'Alice',
                ],
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testOrHavingNotIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->having(
                column: 'name',
                value: 'nobody',
            )
            ->orHavingNotIn(
                column: 'name',
                values: [
                    'Alice',
                    'Bob',
                ],
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testHavingNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('email')
            ->groupBy('email')
            ->havingNull(
                column: 'email',
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testHavingNotNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('email')
            ->groupBy('email')
            ->havingNotNull(
                column: 'email',
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testOrHavingNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('email')
            ->groupBy('email')
            ->having(
                column: 'email',
                value: 'nobody',
            )
            ->orHavingNull(
                column: 'email',
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testOrHavingNotNull(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('email')
            ->groupBy('email')
            ->having(
                column: 'email',
                value: 'nobody',
            )
            ->orHavingNotNull(
                column: 'email',
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testHavingBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->groupBy('id')
            ->havingBetween(
                column: 'id',
                from: 2,
                to: 3,
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testHavingNotBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->groupBy('id')
            ->havingNotBetween(
                column: 'id',
                from: 2,
                to: 3,
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testOrHavingBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->groupBy('id')
            ->having(
                column: 'id',
                value: 999,
            )
            ->orHavingBetween(
                column: 'id',
                from: 1,
                to: 2,
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testOrHavingNotBetween(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->groupBy('id')
            ->having(
                column: 'id',
                value: 999,
            )
            ->orHavingNotBetween(
                column: 'id',
                from: 100,
                to: 200,
            )
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testLimitRestrictsResultRowCount(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->orderBy(
                column: 'id',
            )
            ->limit(
                limit: 2,
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testLimitWithOffsetSkipsRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $names = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->orderBy(
                    column: 'id',
                )
                ->limit(
                    limit: 2,
                    offset: 1,
                )
                ->execute() as $row
        ) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Bob',
                'Charlie',
            ],
            $names,
        );
    }

    public function testInnerJoinReturnsMatchingRowsOnly(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $result = $this->connection->select(
            table: 'users',
        )
            ->innerJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
            )
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testLeftJoinIncludesUnmatchedLeftSide(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $result = $this->connection->select(
            table: 'users',
        )
            ->leftJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
            )
            ->execute();

        self::assertCount(
            4,
            $result,
        );
    }

    public function testRightJoinIncludesUnmatchedRightSide(): void
    {
        $this->createUsersSchema();
        $this->createPostsSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (user_id, title) VALUES (1, 'Post 1')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (user_id, title) VALUES (99, 'Orphan post')",
            native: true,
        );

        $result = $this->connection->select(
            table: 'users',
        )
            ->rightJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testCrossJoinProducesCartesianProduct(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $result = $this->connection->select(
            table: 'users',
        )
            ->crossJoin(
                table: 'posts',
            )
            ->execute();

        self::assertCount(
            9,
            $result,
        );
    }

    public function testFetchReturnsHydratedObject(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $user = $this->connection->select(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->fetch(
                class: HydratableTestUser::class,
            );

        self::assertInstanceOf(
            HydratableTestUser::class,
            $user,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );
    }

    public function testFetchReturnsNullOnEmptyResult(): void
    {
        $this->createUsersSchema();

        $user = $this->connection->select(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'nobody',
            )
            ->fetch(
                class: HydratableTestUser::class,
            );

        self::assertNull(
            $user,
        );
    }

    public function testFetchWithClosureAppliesClosure(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $wrapped = $this->connection->select(
            table: 'users',
        )
            ->where(
                column: 'name',
                value: 'Alice',
            )
            ->fetch(
                class: static function (array $properties): \stdClass {
                    /** @var string $name */
                    /** @var string $name */
                    $name = $properties['name'];

                    $dto = new \stdClass();
                    $dto->wrapped = '[' . $name . ']';

                    return $dto;
                },
            );

        self::assertNotNull(
            $wrapped,
        );

        self::assertSame(
            '[Alice]',
            $wrapped->wrapped,
        );
    }

    public function testFetchAllYieldsGenerator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $users = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->orderBy(
                    column: 'id',
                )
                ->fetchAll(
                    class: HydratableTestUser::class,
                ) as $user
        ) {
            $users[] = $user->name;
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $users,
        );
    }

    public function testOrderByAscendingViaStringInput(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $names = [];

        foreach (
            $this->connection->select(
                table: 'users',
            )
                ->orderBy(
                    column: 'name',
                    direction: 'asc',
                )
                ->execute() as $row
        ) {
            $names[] = $row->properties['name'];
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $names,
        );
    }

    public function testOrderByUnknownStringDirectionThrows(): void
    {
        $this->createUsersSchema();

        $this->expectException(SqlException::class);

        $this->connection->select(
            table: 'users',
        )->orderBy(
            column: 'name',
            direction: 'sideways',
        );
    }

    public function testOrHavingWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('name')
            ->groupBy('name')
            ->having(
                column: 'name',
                value: 'nobody',
            )
            ->orHaving(
                column: 'name',
                value: 'Alice',
                operator: '!=',
            )
            ->execute();

        self::assertCount(
            2,
            $result,
        );
    }

    public function testHavingBetweenWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->groupBy('id')
            ->havingBetween(
                column: 'id',
                from: 100,
                to: 200,
                operator: 'NOT_BETWEEN',
            )
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testOrHavingBetweenWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('id')
            ->groupBy('id')
            ->having(
                column: 'id',
                value: 999,
            )
            ->orHavingBetween(
                column: 'id',
                from: 100,
                to: 200,
                operator: 'NOT_BETWEEN',
            )
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testInnerJoinWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $result = $this->connection->select(
            table: 'users',
        )
            ->innerJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
                operator: '=',
            )
            ->execute();

        self::assertCount(
            3,
            $result,
        );
    }

    public function testLeftJoinWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $result = $this->connection->select(
            table: 'users',
        )
            ->leftJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
                operator: '=',
            )
            ->execute();

        self::assertCount(
            4,
            $result,
        );
    }

    public function testRightJoinWithStringOperator(): void
    {
        $this->createUsersSchema();
        $this->createPostsSchema();

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'a@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO posts (user_id, title) VALUES (1, 'Post 1')",
            native: true,
        );

        $result = $this->connection->select(
            table: 'users',
        )
            ->rightJoin(
                table: 'posts',
                first: 'users.id',
                second: 'posts.user_id',
                operator: '=',
            )
            ->execute();

        self::assertCount(
            1,
            $result,
        );
    }

    public function testUnknownJoinStringOperatorThrows(): void
    {
        $this->createUsersSchema();
        $this->createPostsSchema();

        $this->expectException(SqlException::class);

        $this->connection->select(
            table: 'users',
        )->innerJoin(
            table: 'posts',
            first: 'users.id',
            second: 'posts.user_id',
            operator: 'nonsense',
        );
    }

    /**
     * @return list<string>
     */
    private function names(
        SelectStatementInterface $statement,
    ): array {
        $names = [];

        foreach ($statement->execute() as $row) {
            /** @var string $name */
            $name = $row->properties['name'];
            $names[] = $name;
        }

        return $names;
    }

    private function postCount(): SelectStatementInterface
    {
        return $this->connection->select(
            table: 'posts',
        )
            ->select('COUNT(*)')
            ->whereColumn(
                column: 'posts.user_id',
                other: 'users.id',
            );
    }
}
