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

namespace Support\Database;

use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait RealDatabaseIntegrationSetup
{
    abstract protected function realDatabaseSkipReason(): ?string;

    protected function setUp(): void
    {
        $reason = $this->realDatabaseSkipReason();

        if ($reason !== null) {
            self::markTestSkipped($reason);
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (isset($this->connection) && $this->connection->isConnected()) {
            SchemaCleaner::dropAllTables(
                connection: $this->connection,
            );
        }

        parent::tearDown();
    }
}
