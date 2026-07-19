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

use Integration\Model\AbstractModelsManagerIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('sqlite3')]
class ModelsManagerIntegrationTest extends AbstractModelsManagerIntegrationTestCase
{
}
