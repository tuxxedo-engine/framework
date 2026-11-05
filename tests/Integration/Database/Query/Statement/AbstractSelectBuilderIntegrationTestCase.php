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
use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;
use Tuxxedo\Database\Query\Statement\CompoundSelectStatementInterface;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Join\JoinConditionInterface;
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

    public function testSelectExplicitStar(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $row = $this->connection->select(
            table: 'users',
        )
            ->select('*')
            ->execute()
            ->fetchAssoc();

        self::assertArrayHasKey(
            'id',
            $row,
        );

        self::assertArrayHasKey(
            'email',
            $row,
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
            ->selectAggregate(AggregateFunction::COUNT)
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

    public function testDistinctOrderByPutsNullsLastAscending(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->seedDuplicateAlice();

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
                    ->distinct()
                    ->select('name', 'email')
                    ->orderBy(
                        column: 'email',
                    ),
            ),
        );
    }

    public function testDistinctOrderByPutsNullsLastDescending(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->seedDuplicateAlice();

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
                    ->distinct()
                    ->select('name', 'email')
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

    public function testAliasesWithAggregateAndGroupBy(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $rows = [];

        foreach (
            $this->connection->select(
                table: 'users',
                alias: 'u',
            )
                ->selectAs('u.name', 'user_name')
                ->selectAggregate(
                    function: AggregateFunction::COUNT,
                    column: 'p.id',
                    alias: 'post_count',
                    distinct: true,
                )
                ->innerJoin(
                    table: 'posts',
                    first: 'p.user_id',
                    second: 'u.id',
                    alias: 'p',
                )
                ->groupBy('u.name')
                ->orderBy('u.name')
                ->execute() as $row
        ) {
            $rows[] = [
                $row->properties['user_name'],
                $row->properties['post_count'],
            ];
        }

        self::assertEquals(
            [
                [
                    'Alice',
                    1,
                ],
                [
                    'Bob',
                    2,
                ],
            ],
            $rows,
        );
    }

    public function testSelfJoinOnColumnStartsWith(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                    alias: 'u',
                )
                    ->selectAs('u.name', 'name')
                    ->innerJoin(
                        table: 'users',
                        alias: 'v',
                        on: static function (JoinConditionInterface $on): void {
                            $on->whereColumnStartsWith('v.email', 'u.name');
                        },
                    )
                    ->orderBy('u.name'),
            ),
        );
    }

    public function testWhereColumnStartsWithIsCaseInsensitive(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->whereColumnStartsWith('email', 'name')
                    ->orderBy('name'),
            ),
        );

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
                    ->where('name', 'Charlie')
                    ->orWhereColumnStartsWith('email', 'name')
                    ->orderBy('name'),
            ),
        );
    }

    public function testWhereColumnStartsWithTreatsPrefixLiterally(): void
    {
        $this->createUsersSchema();

        foreach (
            [
                "('a_', 'ab@example.test')",
                "('c%', 'cx@example.test')",
                "('d!', 'd!x@example.test')",
                "('e_', 'e_x@example.test')",
            ] as $values
        ) {
            $this->connection->query(
                sql: 'INSERT INTO users (name, email) VALUES ' . $values,
                native: true,
            );
        }

        self::assertSame(
            [
                'd!',
                'e_',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->whereColumnStartsWith('email', 'name')
                    ->orderBy('name'),
            ),
        );
    }

    public function testWhereStartsWithEscapesWildcards(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Post by Alice',
                'Post by Bob',
            ],
            $this->titles(static fn (SelectStatementInterface $select): SelectStatementInterface => $select->whereStartsWith('posts.title', 'post by')),
        );

        self::assertSame(
            [],
            $this->titles(static fn (SelectStatementInterface $select): SelectStatementInterface => $select->whereStartsWith('posts.title', 'Post_by')),
        );

        self::assertSame(
            [
                'Another Bob post',
                'Post by Alice',
            ],
            $this->titles(
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select
                    ->where('posts.title', 'Another Bob post')
                    ->orWhereStartsWith('posts.title', 'Post by A'),
            ),
        );
    }

    public function testJoinOnCallbackUsesWhereVocabulary(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->select('users.name')
                    ->innerJoin(
                        table: 'posts',
                        on: static function (JoinConditionInterface $on): void {
                            $on
                                ->whereColumn('posts.user_id', 'users.id')
                                ->whereGroup(
                                    static function (JoinConditionInterface $titles): void {
                                        $titles
                                            ->where('posts.title', 'Post by Alice')
                                            ->orWhere('posts.title', 'Another Bob post');
                                    },
                                )
                                ->whereNotNull('posts.title')
                                ->whereIn(
                                    'posts.user_id',
                                    [
                                        1,
                                        2,
                                    ],
                                );
                        },
                    )
                    ->orderBy('users.name'),
            ),
        );
    }

    public function testLeftJoinOnCallbackKeepsUnmatchedRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $result = $this->connection->select(
            table: 'users',
        )
            ->select('users.name')
            ->leftJoin(
                table: 'posts',
                on: static function (JoinConditionInterface $on): void {
                    $on
                        ->whereColumn('posts.user_id', 'users.id')
                        ->orWhereGroup(
                            static function (JoinConditionInterface $never): void {
                                $never
                                    ->whereNull('posts.id')
                                    ->orWhereColumn('posts.title', 'users.name')
                                    ->whereStartsWith('posts.title', 'zz')
                                    ->orWhereStartsWith('posts.title', 'yy')
                                    ->whereColumnStartsWith('posts.title', 'users.email')
                                    ->orWhereColumnStartsWith('posts.title', 'users.email');
                            },
                        );
                },
            )
            ->execute();

        self::assertCount(
            4,
            $result,
        );
    }

    public function testJoinNeedsExactlyOneConditionForm(): void
    {
        foreach (
            [
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->innerJoin(
                    table: 'posts',
                ),
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->innerJoin(
                    table: 'posts',
                    first: 'posts.user_id',
                    second: 'users.id',
                    on: static function (JoinConditionInterface $on): void {
                        $on->whereColumn('posts.user_id', 'users.id');
                    },
                ),
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->innerJoin(
                    table: 'posts',
                    on: static function (JoinConditionInterface $on): void {
                    },
                ),
            ] as $join
        ) {
            try {
                $join(
                    $this->connection->select(
                        table: 'users',
                    ),
                );

                self::fail('Expected SqlException for an invalid join condition');
            } catch (SqlException $exception) {
                self::assertStringContainsString('posts', $exception->getMessage());
            }
        }
    }

    public function testCrossJoinWithAlias(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertCount(
            9,
            $this->connection->select(
                table: 'users',
            )
                ->crossJoin(
                    table: 'posts',
                    alias: 'p',
                )
                ->execute(),
        );
    }

    public function testSelectRawWithAlias(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $row = $this->connection->select(
            table: 'users',
        )
            ->selectRaw('1 + 1', 'two')
            ->limit(1)
            ->execute()
            ->fetch();

        self::assertEquals(2, $row->properties['two']);
    }

    public function testHavingAggregateRendersTheAggregate(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $grouped = fn (): SelectStatementInterface => $this->connection->select(
            table: 'users',
        )
            ->select('users.name')
            ->innerJoin(
                table: 'posts',
                first: 'posts.user_id',
                second: 'users.id',
            )
            ->groupBy('users.name')
            ->orderBy('users.name');

        self::assertSame(
            [
                'Bob',
            ],
            $this->names(
                $grouped()->havingAggregate(AggregateFunction::COUNT, 'posts.id', 2, '>='),
            ),
        );

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $this->names(
                $grouped()
                    ->havingAggregate(AggregateFunction::COUNT, 'posts.id', 2, '>=')
                    ->orHavingAggregate(AggregateFunction::COUNT, 'posts.id', 1, distinct: true),
            ),
        );
    }

    public function testSelectFromFiltersOnAggregatedSubquery(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $counts = $this->connection->select(
            table: 'posts',
        )
            ->select('user_id')
            ->selectCount(alias: 'post_count')
            ->groupBy('user_id');

        self::assertSame(
            [
                'Bob',
            ],
            $this->names(
                $this->connection->selectFrom(
                    subquery: $counts,
                    alias: 'c',
                )
                    ->selectAs('u.name', 'name')
                    ->innerJoin(
                        table: 'users',
                        first: 'u.id',
                        second: 'c.user_id',
                        alias: 'u',
                    )
                    ->where('c.post_count', 2, '>='),
            ),
        );
    }

    public function testSelectFromFiltersOnWindowFunction(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $ranked = $this->connection->select(
            table: 'posts',
        )
            ->select('title')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY id DESC)', 'rn');

        self::assertSame(
            [
                'Another Bob post',
                'Post by Alice',
            ],
            $this->names(
                $this->connection->selectFrom(
                    subquery: $ranked,
                    alias: 'ranked',
                )
                    ->selectAs('ranked.title', 'name')
                    ->where('ranked.rn', 1)
                    ->orderBy('ranked.title'),
            ),
        );
    }

    public function testSelectFromKeepsParametersOfBothLevelsApart(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $bobPosts = $this->connection->select(
            table: 'posts',
        )
            ->where('user_id', 2);

        self::assertSame(
            [
                'Another Bob post',
            ],
            $this->names(
                $this->connection->selectFrom(
                    subquery: $bobPosts,
                    alias: 'p',
                )
                    ->selectAs('p.title', 'name')
                    ->whereStartsWith('p.title', 'Another'),
            ),
        );
    }

    public function testSelectFromQualifiesStarWhenJoined(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $row = $this->connection->selectFrom(
            subquery: $this->connection->select(
                table: 'posts',
            )
                ->where('user_id', 1),
            alias: 'p',
        )
            ->innerJoin(
                table: 'users',
                first: 'u.id',
                second: 'p.user_id',
                alias: 'u',
            )
            ->execute()
            ->fetch();

        self::assertSame('Post by Alice', $row->properties['title']);
        self::assertArrayNotHasKey('name', $row->properties);
    }

    public function testUnionRemovesDuplicatesAndUnionAllKeepsThem(): void
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
                $this->connection->union(
                    $this->usersNamed('Alice', 'Bob'),
                    $this->usersNamed('Bob', 'Charlie'),
                )
                    ->orderBy('name'),
            ),
        );

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Bob',
                'Charlie',
            ],
            $this->names(
                $this->connection->unionAll(
                    $this->usersNamed('Alice', 'Bob'),
                    $this->usersNamed('Bob', 'Charlie'),
                )
                    ->orderBy('name'),
            ),
        );
    }

    public function testUnionChainsLeftToRight(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Bob',
                'Charlie',
                'Charlie',
            ],
            $this->names(
                $this->connection->union(
                    $this->usersNamed('Alice', 'Bob'),
                    $this->usersNamed('Bob', 'Charlie'),
                )
                    ->unionAll($this->usersNamed('Bob', 'Charlie'))
                    ->orderBy('name'),
            ),
        );

        self::assertSame(
            [
                'Alice',
                'Bob',
                'Charlie',
            ],
            $this->names(
                $this->connection->unionAll(
                    $this->usersNamed('Alice', 'Bob'),
                    $this->usersNamed('Bob', 'Charlie'),
                )
                    ->union($this->usersNamed('Bob'))
                    ->orderBy('name'),
            ),
        );
    }

    public function testNestedUnionKeepsItsGrouping(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Alice',
            ],
            $this->names(
                $this->connection->unionAll(
                    $this->connection->union(
                        $this->usersNamed('Alice'),
                        $this->usersNamed('Alice'),
                    ),
                    $this->usersNamed('Alice'),
                ),
            ),
        );
    }

    public function testUnionOrdersAndLimitsTheWholeResult(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Post by Alice',
                'Charlie',
            ],
            $this->names(
                $this->connection->unionAll(
                    $this->usersNamed('Alice', 'Bob', 'Charlie'),
                    $this->connection->select(
                        table: 'posts',
                    )
                        ->selectAs('title', 'name'),
                )
                    ->orderBy('name', 'DESC')
                    ->limit(2, 1),
            ),
        );
    }

    public function testUnionOrderByOnSameColumnReplacesDirection(): void
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
                $this->connection->union(
                    $this->usersNamed('Alice', 'Bob'),
                    $this->usersNamed('Charlie'),
                )
                    ->orderBy('name')
                    ->orderBy('name', 'DESC'),
            ),
        );
    }

    public function testUnionMemberKeepsItsOwnOrderAndLimit(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Charlie',
            ],
            $this->names(
                $this->connection->union(
                    $this->usersNamed('Alice', 'Bob', 'Charlie')
                        ->orderBy('name', 'DESC')
                        ->limit(1),
                    $this->usersNamed('Alice'),
                )
                    ->orderBy('name'),
            ),
        );
    }

    public function testUnionInsideSelectFrom(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Post by Alice',
                'Post by Bob',
            ],
            $this->names(
                $this->connection->selectFrom(
                    subquery: $this->connection->unionAll(
                        $this->usersNamed('Alice', 'Bob'),
                        $this->connection->select(
                            table: 'posts',
                        )
                            ->selectAs('title', 'name')
                            ->where('user_id', 1, '>='),
                    ),
                    alias: 'u',
                )
                    ->selectAs('u.name', 'name')
                    ->whereStartsWith('u.name', 'Post')
                    ->orderBy('u.name'),
            ),
        );
    }

    public function testUnionInsideWhereIn(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'users',
                )
                    ->selectAs('name', 'name')
                    ->whereIn(
                        column: 'id',
                        values: $this->connection->union(
                            $this->connection->select(
                                table: 'posts',
                            )
                                ->select('user_id')
                                ->where('title', 'Post by Alice'),
                            $this->connection->select(
                                table: 'posts',
                            )
                                ->select('user_id')
                                ->where('title', 'Another Bob post'),
                        ),
                    )
                    ->orderBy('name'),
            ),
        );
    }

    public function testUnionFetchesHydratedRows(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $union = $this->connection->unionAll(
            $this->connection->select(
                table: 'users',
            )
                ->where('name', 'Alice'),
            $this->connection->select(
                table: 'users',
            )
                ->where('name', 'Bob'),
        )
            ->orderBy('name');

        $user = $union->fetch(HydratableTestUser::class);

        self::assertInstanceOf(
            HydratableTestUser::class,
            $user,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );

        $names = [];

        foreach ($union->fetchAll(HydratableTestUser::class) as $row) {
            $names[] = $row->name;
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $names,
        );

        self::assertNull(
            $this->connection->union(
                $this->usersNamed('nobody'),
                $this->usersNamed('nobody either'),
            )
                ->fetch(HydratableTestUser::class),
        );
    }

    public function testAggregateShortcutsSelectTheirFunction(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $row = $this->connection->select(
            table: 'posts',
        )
            ->selectCount(alias: 'total')
            ->selectCount('user_id', 'authors', distinct: true)
            ->selectSum('id', 'id_sum')
            ->selectAvg('id', 'id_avg')
            ->selectMin('id', 'id_min')
            ->selectMax('id', 'id_max')
            ->execute()
            ->fetch();

        self::assertEquals(3, $row->properties['total']);
        self::assertEquals(2, $row->properties['authors']);
        self::assertEquals(6, $row->properties['id_sum']);
        self::assertEquals(2.0, $row->properties['id_avg']);
        self::assertEquals(1, $row->properties['id_min']);
        self::assertEquals(3, $row->properties['id_max']);
    }

    public function testHavingShortcutsRenderTheirFunction(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();
        $this->createPostsSchema();
        $this->seedPosts();

        $grouped = fn (): SelectStatementInterface => $this->connection->select(
            table: 'users',
        )
            ->select('users.name')
            ->innerJoin(
                table: 'posts',
                first: 'posts.user_id',
                second: 'users.id',
            )
            ->groupBy('users.name')
            ->orderBy('users.name');

        foreach (
            [
                $grouped()->havingCount(2, '>=', 'posts.id'),
                $grouped()->havingSum('posts.id', 5),
                $grouped()->havingAvg('posts.id', 2, '>'),
                $grouped()->havingMin('posts.id', 2),
                $grouped()->havingMax('posts.id', 3),
            ] as $statement
        ) {
            self::assertSame(
                [
                    'Bob',
                ],
                $this->names($statement),
            );
        }

        self::assertSame(
            [
                'Alice',
                'Bob',
            ],
            $this->names(
                $grouped()
                    ->havingCount(99)
                    ->orHavingSum('posts.id', 1)
                    ->orHavingAvg('posts.id', 2, '>')
                    ->orHavingMin('posts.id', 99)
                    ->orHavingMax('posts.id', 99)
                    ->orHavingCount(99, distinct: true, column: 'posts.id'),
            ),
        );
    }

    public function testAggregateWithoutColumnOnlyAllowsPlainCount(): void
    {
        foreach (
            [
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->selectAggregate(AggregateFunction::SUM),
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->selectAggregate(AggregateFunction::COUNT, distinct: true),
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->havingAggregate(AggregateFunction::MAX, null, 1),
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->selectCount(distinct: true),
                static fn (SelectStatementInterface $select): SelectStatementInterface => $select->havingCount(1, distinct: true),
            ] as $aggregate
        ) {
            try {
                $aggregate(
                    $this->connection->select(
                        table: 'users',
                    ),
                );

                self::fail('Expected SqlException for an aggregate without a column');
            } catch (SqlException $exception) {
                self::assertStringContainsString('needs a column', $exception->getMessage());
            }
        }
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
            ->select('name')
            ->selectAggregate(AggregateFunction::COUNT)
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

    public function testWithNamesAQueryForTheMainSelect(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'bobs',
                )
                    ->with(
                        name: 'bobs',
                        query: $this->usersNamed('Bob'),
                    )
                    ->selectAs('bobs.name', 'name'),
            ),
        );
    }

    public function testWithColumnListRenamesColumns(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
            ],
            $this->names(
                $this->connection->select(
                    table: 'people',
                )
                    ->with(
                        name: 'people',
                        query: $this->connection->select(
                            table: 'users',
                        )
                            ->select('id', 'name'),
                        columns: [
                            'person_id',
                            'person_name',
                        ],
                    )
                    ->selectAs('people.person_name', 'name')
                    ->where('people.person_id', 1),
            ),
        );
    }

    public function testWithChainsExpressionsThatReferenceEachOther(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Bob',
            ],
            $this->names(
                $this->connection->select(
                    table: 'narrowed',
                )
                    ->with(
                        name: 'picked',
                        query: $this->usersNamed('Alice', 'Bob'),
                    )
                    ->with(
                        name: 'narrowed',
                        query: $this->connection->select(
                            table: 'picked',
                        )
                            ->where('picked.name', 'Bob'),
                    )
                    ->selectAs('narrowed.name', 'name')
                    ->where('narrowed.name', 'Alice', '!='),
            ),
        );
    }

    public function testWithRecursiveWalksATree(): void
    {
        $this->createNodesSchema();
        $this->seedNodes();

        self::assertSame(
            [
                'a',
                'a1',
                'a1x',
            ],
            $this->names(
                $this->subtree(2)
                    ->selectAs('tree.name', 'name')
                    ->orderBy('tree.depth'),
            ),
        );
    }

    public function testWithRecursiveCarriesComputedColumns(): void
    {
        $this->createNodesSchema();
        $this->seedNodes();

        $depths = [];

        foreach ($this->subtree(1)->orderBy('tree.depth')->orderBy('tree.name')->execute() as $row) {
            /** @var string $name */
            $name = $row->properties['name'];

            /** @var int|string $depth */
            $depth = $row->properties['depth'];
            $depths[$name] = (int) $depth;
        }

        self::assertSame(
            [
                'root' => 0,
                'a' => 1,
                'b' => 1,
                'a1' => 2,
                'a1x' => 3,
            ],
            $depths,
        );
    }

    public function testWithRecursiveDistinctStopsAtCycles(): void
    {
        $this->createNodesSchema();
        $this->seedNodes();

        self::assertSame(
            [
                'loop1',
                'loop2',
            ],
            $this->names(
                $this->connection->select(
                    table: 'tree',
                )
                    ->withRecursive(
                        name: 'tree',
                        anchor: $this->connection->select(
                            table: 'nodes',
                        )
                            ->select('id', 'name')
                            ->where('id', 6),
                        recursive: $this->connection->select(
                            table: 'nodes',
                            alias: 'n',
                        )
                            ->select('n.id', 'n.name')
                            ->innerJoin(
                                table: 'tree',
                                first: 'tree.id',
                                second: 'n.parent_id',
                            ),
                        distinct: true,
                    )
                    ->selectAs('tree.name', 'name')
                    ->orderBy('tree.name'),
            ),
        );
    }

    public function testWithInsideWhereInSubquery(): void
    {
        $this->createNodesSchema();
        $this->seedNodes();

        self::assertSame(
            [
                'a1',
                'a1x',
            ],
            $this->names(
                $this->connection->select(
                    table: 'nodes',
                )
                    ->selectAs('nodes.name', 'name')
                    ->whereIn(
                        column: 'nodes.id',
                        values: $this->subtree(4)
                            ->select('tree.id'),
                    )
                    ->orderBy('nodes.name'),
            ),
        );
    }

    public function testWithOnUnionIsVisibleToEveryMember(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        self::assertSame(
            [
                'Alice',
                'Charlie',
            ],
            $this->names(
                $this->connection->union(
                    $this->connection->select(
                        table: 'picked',
                    )
                        ->selectAs('picked.name', 'name')
                        ->where('picked.name', 'Alice'),
                    $this->connection->select(
                        table: 'picked',
                    )
                        ->selectAs('picked.name', 'name')
                        ->where('picked.name', 'Charlie'),
                )
                    ->with(
                        name: 'picked',
                        query: $this->usersNamed('Alice', 'Charlie'),
                    )
                    ->orderBy('name'),
            ),
        );
    }

    public function testWithRecursiveOnUnionKeepsParametersApart(): void
    {
        $this->createNodesSchema();
        $this->seedNodes();

        self::assertSame(
            [
                'a1x',
                'b',
            ],
            $this->names(
                $this->connection->unionAll(
                    $this->connection->select(
                        table: 'tree',
                    )
                        ->selectAs('tree.name', 'name')
                        ->where('tree.depth', 2),
                    $this->connection->select(
                        table: 'nodes',
                    )
                        ->selectAs('nodes.name', 'name')
                        ->where('nodes.id', 3),
                )
                    ->withRecursive(
                        name: 'tree',
                        anchor: $this->connection->select(
                            table: 'nodes',
                        )
                            ->select('id', 'name')
                            ->selectRaw('0', 'depth')
                            ->where('id', 2),
                        recursive: $this->connection->select(
                            table: 'nodes',
                            alias: 'n',
                        )
                            ->select('n.id', 'n.name')
                            ->selectRaw('tree.depth + 1', 'depth')
                            ->innerJoin(
                                table: 'tree',
                                first: 'tree.id',
                                second: 'n.parent_id',
                            ),
                    )
                    ->orderBy('name'),
            ),
        );
    }

    public function testWithDuplicateNameThrows(): void
    {
        $statement = $this->connection->select(
            table: 'bobs',
        )
            ->with(
                name: 'bobs',
                query: $this->usersNamed('Bob'),
            );

        try {
            $statement->with(
                name: 'bobs',
                query: $this->usersNamed('Alice'),
            );

            self::fail('Expected SqlException was not thrown');
        } catch (SqlException $exception) {
            self::assertStringContainsString(
                '`bobs` is already defined',
                $exception->getMessage(),
            );
        }
    }

    private function seedDuplicateAlice(): void
    {
        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.test')",
            native: true,
        );
    }

    /**
     * @return list<string>
     */
    private function names(
        SelectStatementInterface|CompoundSelectStatementInterface $statement,
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
            ->selectAggregate(AggregateFunction::COUNT)
            ->whereColumn(
                column: 'posts.user_id',
                other: 'users.id',
            );
    }

    private function usersNamed(
        string $name,
        string ...$names,
    ): SelectStatementInterface {
        return $this->connection->select(
            table: 'users',
        )
            ->selectAs('name', 'name')
            ->whereIn(
                column: 'name',
                values: [
                    $name,
                    ...$names,
                ],
            );
    }

    private function subtree(
        int $rootId,
    ): SelectStatementInterface {
        return $this->connection->select(
            table: 'tree',
        )
            ->withRecursive(
                name: 'tree',
                anchor: $this->connection->select(
                    table: 'nodes',
                )
                    ->select('id', 'name')
                    ->selectRaw('0', 'depth')
                    ->where('id', $rootId),
                recursive: $this->connection->select(
                    table: 'nodes',
                    alias: 'n',
                )
                    ->select('n.id', 'n.name')
                    ->selectRaw('tree.depth + 1', 'depth')
                    ->innerJoin(
                        table: 'tree',
                        first: 'tree.id',
                        second: 'n.parent_id',
                    ),
            );
    }

    /**
     * @param \Closure(SelectStatementInterface): SelectStatementInterface $configure
     * @return list<string>
     */
    private function titles(
        \Closure $configure,
    ): array {
        return $this->names(
            $configure(
                $this->connection->select(
                    table: 'posts',
                )
                    ->selectAs('posts.title', 'name'),
            )
                ->orderBy('posts.title'),
        );
    }
}
