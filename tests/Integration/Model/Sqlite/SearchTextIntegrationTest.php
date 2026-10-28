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

namespace Integration\Model\Sqlite;

use Fixture\Model\Article;
use Integration\Model\AbstractModelIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Database\SqlException;

#[RequiresPhpExtension('sqlite3')]
class SearchTextIntegrationTest extends AbstractModelIntegrationTestCase
{
    public function testCreateTableWithSearchTextIsUnsupported(): void
    {
        try {
            $this->modelsManager
                ->createTable(Article::class)
                ->execute();

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('does not support full-text search', $exception->getMessage());
        }
    }
}
