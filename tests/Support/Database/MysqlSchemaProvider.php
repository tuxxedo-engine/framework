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

class MysqlSchemaProvider implements SchemaProvider
{
    public function usersSchemaSql(): string
    {
        return 'CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) NULL)';
    }

    public function postsSchemaSql(): string
    {
        return 'CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(255) NOT NULL)';
    }

    public function typesSchemaSql(): string
    {
        return 'CREATE TABLE types (id INT AUTO_INCREMENT PRIMARY KEY, num INT NULL, ratio DOUBLE NULL, flag TINYINT NULL)';
    }

    public function countersSchemaSql(): string
    {
        return 'CREATE TABLE counters (id INT AUTO_INCREMENT PRIMARY KEY, num INT NOT NULL, ratio DOUBLE NOT NULL)';
    }

    public function widgetsSchemaSql(): string
    {
        return 'CREATE TABLE widgets (id INT PRIMARY KEY)';
    }
}
