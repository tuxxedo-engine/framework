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

namespace Tuxxedo\Database\Driver;

enum DefaultDriver
{
    case MYSQL;
    case PDO;
    case PDO_MYSQL;
    case PDO_PGSQL;
    case PDO_SQLITE;
    case PGSQL;
    case SQLITE;
}
