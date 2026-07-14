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

class SqliteSchemaProvider implements SchemaProvider
{
    public function usersSchemaSql(): string
    {
        return 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT)';
    }

    public function postsSchemaSql(): string
    {
        return 'CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL)';
    }

    public function typesSchemaSql(): string
    {
        return 'CREATE TABLE types (id INTEGER PRIMARY KEY AUTOINCREMENT, num INTEGER, ratio REAL, flag INTEGER)';
    }

    public function countersSchemaSql(): string
    {
        return 'CREATE TABLE counters (id INTEGER PRIMARY KEY AUTOINCREMENT, num INTEGER NOT NULL, ratio REAL NOT NULL)';
    }

    public function widgetsSchemaSql(): string
    {
        return 'CREATE TABLE widgets (id INTEGER PRIMARY KEY)';
    }
}
