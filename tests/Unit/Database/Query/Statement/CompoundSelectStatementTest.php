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

namespace Unit\Database\Query\Statement;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Statement\CompoundSelectStatement;
use Tuxxedo\Database\Query\Statement\SelectStatement;

class CompoundSelectStatementTest extends TestCase
{
    public function testCompileWithoutConnectionThrows(): void
    {
        $union = new CompoundSelectStatement(
            first: new SelectStatement(
                table: 'users',
            ),
        );

        try {
            $union->compile();

            self::fail('Expected DatabaseException');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('no connection', $exception->getMessage());
        }
    }

    public function testExecuteWithoutConnectionThrows(): void
    {
        $union = new CompoundSelectStatement(
            first: new SelectStatement(
                table: 'users',
            ),
        );

        try {
            $union->execute();

            self::fail('Expected DatabaseException');
        } catch (DatabaseException $exception) {
            self::assertStringContainsString('no connection', $exception->getMessage());
        }
    }
}
