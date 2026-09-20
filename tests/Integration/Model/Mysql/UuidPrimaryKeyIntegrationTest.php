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

namespace Integration\Model\Mysql;

use Integration\Model\AbstractUuidPrimaryKeyIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('mysqli')]
class UuidPrimaryKeyIntegrationTest extends AbstractUuidPrimaryKeyIntegrationTestCase
{
}
