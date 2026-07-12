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

namespace Unit\Database\Query\Parser;

use PHPUnit\Framework\TestCase;
use Support\Database\StubDialect;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\SqlException;

class StatementParserTest extends TestCase
{
    private function makeParser(
        ?StubDialect $dialect = null,
    ): StatementParser {
        return new StatementParser(
            dialect: $dialect ?? new StubDialect(),
        );
    }

    public function testEmptyStringPassesThrough(): void
    {
        $result = $this->makeParser()->parse(
            sql: '',
        );

        self::assertSame(
            '',
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testPlainSqlWithoutPlaceholdersPassesThrough(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT 1 FROM users WHERE deleted = 0',
        );

        self::assertSame(
            'SELECT 1 FROM users WHERE deleted = 0',
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testColonFollowedByNonIdentifierIsLiteral(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT 1: FROM t',
        );

        self::assertSame(
            'SELECT 1: FROM t',
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testColonAtEndOfInputIsLiteral(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT 1:',
        );

        self::assertSame(
            'SELECT 1:',
            $result->sql,
        );
    }

    public function testColonFollowedBySpaceIsLiteral(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'x: y',
        );

        self::assertSame(
            'x: y',
            $result->sql,
        );
    }

    public function testSingleNamedPlaceholderIsReplaced(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * FROM users WHERE id = :id',
            parameters: [
                'id' => 42,
            ],
        );

        self::assertSame(
            'SELECT * FROM users WHERE id = $1',
            $result->sql,
        );

        self::assertSame(
            [
                42,
            ],
            $result->parameters,
        );
    }

    public function testMultipleNamedPlaceholdersEmitIncrementalSlots(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE a = :a AND b = :b AND c = :c',
            parameters: [
                'a' => 1,
                'b' => 'two',
                'c' => 3.14,
            ],
        );

        self::assertSame(
            'SELECT * WHERE a = $1 AND b = $2 AND c = $3',
            $result->sql,
        );

        self::assertSame(
            [
                1,
                'two',
                3.14,
            ],
            $result->parameters,
        );
    }

    public function testPlaceholderNameSupportsDigitsAndUnderscores(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT :col_1, :x2, :_leading',
            parameters: [
                'col_1' => 'a',
                'x2' => 'b',
                '_leading' => 'c',
            ],
        );

        self::assertSame(
            'SELECT $1, $2, $3',
            $result->sql,
        );

        self::assertSame(
            [
                'a',
                'b',
                'c',
            ],
            $result->parameters,
        );
    }

    public function testSameNamedPlaceholderTwiceEmitsTwoSlotsBoundTwice(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE a = :name OR b = :name',
            parameters: [
                'name' => 'alice',
            ],
        );

        self::assertSame(
            'SELECT * WHERE a = $1 OR b = $2',
            $result->sql,
        );

        self::assertSame(
            [
                'alice',
                'alice',
            ],
            $result->parameters,
        );
    }

    public function testPlaceholderCanBindNullBooleanFloat(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'INSERT INTO t (a, b, c) VALUES (:a, :b, :c)',
            parameters: [
                'a' => null,
                'b' => true,
                'c' => 2.5,
            ],
        );

        self::assertSame(
            'INSERT INTO t (a, b, c) VALUES ($1, $2, $3)',
            $result->sql,
        );

        self::assertSame(
            [
                null,
                true,
                2.5,
            ],
            $result->parameters,
        );
    }

    public function testUnboundPlaceholderThrows(): void
    {
        $this->expectException(SqlException::class);

        $this->makeParser()->parse(
            sql: 'SELECT :missing',
        );
    }

    public function testNamedPlaceholderBoundToArrayThrows(): void
    {
        $this->expectException(SqlException::class);

        $this->makeParser()->parse(
            sql: 'SELECT :ids',
            parameters: [
                'ids' => [
                    1,
                    2,
                ],
            ],
        );
    }

    public function testArrayPlaceholderExpandsToMultipleSlots(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE id IN (:ids[])',
            parameters: [
                'ids' => [
                    1,
                    2,
                    3,
                ],
            ],
        );

        self::assertSame(
            'SELECT * WHERE id IN ($1, $2, $3)',
            $result->sql,
        );

        self::assertSame(
            [
                1,
                2,
                3,
            ],
            $result->parameters,
        );
    }

    public function testArrayPlaceholderWithSingleElement(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE id IN (:ids[])',
            parameters: [
                'ids' => [
                    42,
                ],
            ],
        );

        self::assertSame(
            'SELECT * WHERE id IN ($1)',
            $result->sql,
        );

        self::assertSame(
            [
                42,
            ],
            $result->parameters,
        );
    }

    public function testArrayPlaceholderWithMixedTypes(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE v IN (:vals[])',
            parameters: [
                'vals' => [
                    1,
                    'two',
                    3.5,
                    true,
                    null,
                ],
            ],
        );

        self::assertSame(
            'SELECT * WHERE v IN ($1, $2, $3, $4, $5)',
            $result->sql,
        );

        self::assertSame(
            [
                1,
                'two',
                3.5,
                true,
                null,
            ],
            $result->parameters,
        );
    }

    public function testArrayPlaceholderIncrementsSlotsAcrossExpansion(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT :prefix, x IN (:ids[]), :suffix',
            parameters: [
                'prefix' => 'a',
                'ids' => [
                    10,
                    20,
                    30,
                ],
                'suffix' => 'b',
            ],
        );

        self::assertSame(
            'SELECT $1, x IN ($2, $3, $4), $5',
            $result->sql,
        );

        self::assertSame(
            [
                'a',
                10,
                20,
                30,
                'b',
            ],
            $result->parameters,
        );
    }

    public function testMultipleArrayPlaceholdersInterleaved(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE a IN (:a[]) OR b IN (:b[])',
            parameters: [
                'a' => [
                    1,
                    2,
                ],
                'b' => [
                    3,
                    4,
                    5,
                ],
            ],
        );

        self::assertSame(
            'SELECT * WHERE a IN ($1, $2) OR b IN ($3, $4, $5)',
            $result->sql,
        );

        self::assertSame(
            [
                1,
                2,
                3,
                4,
                5,
            ],
            $result->parameters,
        );
    }

    public function testArrayPlaceholderBoundToNonArrayThrows(): void
    {
        $this->expectException(SqlException::class);

        $this->makeParser()->parse(
            sql: 'SELECT * WHERE id IN (:ids[])',
            parameters: [
                'ids' => 42,
            ],
        );
    }

    public function testArrayPlaceholderBoundToEmptyArrayThrows(): void
    {
        $this->expectException(SqlException::class);

        $this->makeParser()->parse(
            sql: 'SELECT * WHERE id IN (:ids[])',
            parameters: [
                'ids' => [],
            ],
        );
    }

    public function testContentInsideDoubleQuotedStringIsPreserved(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT "hello world" FROM t',
        );

        self::assertSame(
            'SELECT "hello world" FROM t',
            $result->sql,
        );
    }

    public function testContentInsideSingleQuotedStringIsPreserved(): void
    {
        $result = $this->makeParser()->parse(
            sql: "SELECT 'hello world' FROM t",
        );

        self::assertSame(
            "SELECT 'hello world' FROM t",
            $result->sql,
        );
    }

    public function testPlaceholderLikePatternInsideQuotedStringIsIgnored(): void
    {
        $result = $this->makeParser()->parse(
            sql: "SELECT ':fake' FROM t WHERE x = :real",
            parameters: [
                'real' => 7,
            ],
        );

        self::assertSame(
            "SELECT ':fake' FROM t WHERE x = \$1",
            $result->sql,
        );

        self::assertSame(
            [
                7,
            ],
            $result->parameters,
        );
    }

    public function testEscapedSingleQuoteInSingleQuotedString(): void
    {
        $result = $this->makeParser()->parse(
            sql: "SELECT 'it''s fine' FROM t WHERE x = :x",
            parameters: [
                'x' => 1,
            ],
        );

        self::assertSame(
            "SELECT 'it''s fine' FROM t WHERE x = \$1",
            $result->sql,
        );

        self::assertSame(
            [
                1,
            ],
            $result->parameters,
        );
    }

    public function testDoubleEscapedSingleQuoteYieldsFourInARow(): void
    {
        $result = $this->makeParser()->parse(
            sql: "SELECT '''' FROM t",
        );

        self::assertSame(
            "SELECT '''' FROM t",
            $result->sql,
        );
    }

    public function testUnterminatedQuoteConsumesRestOfInput(): void
    {
        $result = $this->makeParser()->parse(
            sql: "SELECT 'still unterminated :x",
            parameters: [
                'x' => 'never used',
            ],
        );

        self::assertSame(
            "SELECT 'still unterminated :x",
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testCustomQuotationsFromDialectAreRespected(): void
    {
        $parser = $this->makeParser(
            dialect: new StubDialect(
                quotations: [
                    '`',
                ],
            ),
        );

        $result = $parser->parse(
            sql: 'SELECT `raw :name literal` FROM t WHERE x = :x',
            parameters: [
                'x' => 1,
            ],
        );

        self::assertSame(
            'SELECT `raw :name literal` FROM t WHERE x = $1',
            $result->sql,
        );

        self::assertSame(
            [
                1,
            ],
            $result->parameters,
        );
    }

    public function testUtf8CharactersOutsideQuotesArePreserved(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT * WHERE name = :name AND note = \'ユーザー\'',
            parameters: [
                'name' => 'æøå',
            ],
        );

        self::assertSame(
            'SELECT * WHERE name = $1 AND note = \'ユーザー\'',
            $result->sql,
        );

        self::assertSame(
            [
                'æøå',
            ],
            $result->parameters,
        );
    }

    public function testUtf8CharactersInOuterLoopArePreserved(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT ユーザー FROM t WHERE x = :x',
            parameters: [
                'x' => 1,
            ],
        );

        self::assertSame(
            'SELECT ユーザー FROM t WHERE x = $1',
            $result->sql,
        );

        self::assertSame(
            [
                1,
            ],
            $result->parameters,
        );
    }

    public function testUtf8CharactersInsideQuotesArePreserved(): void
    {
        $result = $this->makeParser()->parse(
            sql: "SELECT 'æøå 日本語 :notplaceholder' FROM t",
        );

        self::assertSame(
            "SELECT 'æøå 日本語 :notplaceholder' FROM t",
            $result->sql,
        );

        self::assertSame(
            [],
            $result->parameters,
        );
    }

    public function testRealisticInsertWithMultiplePlaceholders(): void
    {
        $result = $this->makeParser()->parse(
            sql: "INSERT INTO users (name, email, note) VALUES (:name, :email, 'it''s ok')",
            parameters: [
                'name' => 'Alice',
                'email' => 'alice@example.test',
            ],
        );

        self::assertSame(
            "INSERT INTO users (name, email, note) VALUES (\$1, \$2, 'it''s ok')",
            $result->sql,
        );

        self::assertSame(
            [
                'Alice',
                'alice@example.test',
            ],
            $result->parameters,
        );
    }

    public function testRealisticSelectWithWhereInArray(): void
    {
        $result = $this->makeParser()->parse(
            sql: 'SELECT id, name FROM users WHERE status = :status AND id IN (:ids[]) ORDER BY id',
            parameters: [
                'status' => 'active',
                'ids' => [
                    1,
                    2,
                    3,
                    4,
                ],
            ],
        );

        self::assertSame(
            'SELECT id, name FROM users WHERE status = $1 AND id IN ($2, $3, $4, $5) ORDER BY id',
            $result->sql,
        );

        self::assertSame(
            [
                'active',
                1,
                2,
                3,
                4,
            ],
            $result->parameters,
        );
    }
}
