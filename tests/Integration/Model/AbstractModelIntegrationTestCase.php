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

namespace Integration\Model;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Support\Model\ModelsManagerFactory;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Model\ModelsManagerInterface;

#[RequiresPhpExtension('sqlite3')]
abstract class AbstractModelIntegrationTestCase extends TestCase
{
    protected ModelsManagerInterface $modelsManager;
    protected ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->modelsManager = ModelsManagerFactory::create();
        $this->connection = $this->modelsManager->connection;
    }

    protected function tearDown(): void
    {
        if (isset($this->connection) && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    protected function createAllFixtureTables(): void
    {
        $this->createUsersTable();
        $this->createProfilesTable();
        $this->createPostsTable();
        $this->createCommentsTable();
        $this->createTagsTable();
        $this->createRolesTable();
        $this->createCategoriesTable();
        $this->createPostTagPivot();
        $this->createUserRolePivot();
    }

    protected function createUsersTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE users (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'name TEXT NOT NULL, ' .
                'email TEXT NOT NULL, ' .
                'isActive INTEGER NOT NULL DEFAULT 1, ' .
                'postCount INTEGER NOT NULL DEFAULT 0, ' .
                'score REAL NOT NULL DEFAULT 0, ' .
                'lastLoginAt TEXT NULL, ' .
                'createdAt TEXT NULL, ' .
                'updatedAt TEXT NULL' .
                ')',
            native: true,
        );
    }

    protected function createProfilesTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE profiles (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'user_id INTEGER NOT NULL, ' .
                'bio TEXT NOT NULL DEFAULT \'\', ' .
                'avatar BLOB NULL, ' .
                'settings TEXT NULL, ' .
                'birthDate TEXT NULL' .
                ')',
            native: true,
        );
    }

    protected function createPostsTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE posts (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'user_id INTEGER NOT NULL, ' .
                'title TEXT NOT NULL, ' .
                'body TEXT NOT NULL DEFAULT \'\', ' .
                'status TEXT NOT NULL DEFAULT \'draft\', ' .
                'publishedAt TEXT NULL, ' .
                'viewCount INTEGER NOT NULL DEFAULT 0, ' .
                'rating TEXT NOT NULL DEFAULT \'0.00\'' .
                ')',
            native: true,
        );
    }

    protected function createCommentsTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE comments (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'post_id INTEGER NOT NULL, ' .
                'user_id INTEGER NOT NULL, ' .
                'body TEXT NOT NULL DEFAULT \'\', ' .
                'createdAt TEXT NULL, ' .
                'deletedAt TEXT NULL' .
                ')',
            native: true,
        );
    }

    protected function createTagsTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE tags (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'slug TEXT NOT NULL, ' .
                'name TEXT NOT NULL, ' .
                'category TEXT NOT NULL DEFAULT \'\'' .
                ')',
            native: true,
        );
    }

    protected function createRolesTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE roles (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                '"key" TEXT NOT NULL, ' .
                'label TEXT NOT NULL, ' .
                'sortOrder INTEGER NOT NULL DEFAULT 0, ' .
                'startsAt TEXT NULL' .
                ')',
            native: true,
        );
    }

    protected function createCategoriesTable(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE categories (' .
                'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
                'parent_id INTEGER NULL, ' .
                'name TEXT NOT NULL, ' .
                'depth INTEGER NOT NULL DEFAULT 0' .
                ')',
            native: true,
        );
    }

    protected function createPostTagPivot(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE post_tag (' .
                'post_id INTEGER NOT NULL, ' .
                'tag_id INTEGER NOT NULL, ' .
                'PRIMARY KEY (post_id, tag_id)' .
                ')',
            native: true,
        );
    }

    protected function createUserRolePivot(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE user_role (' .
                'user_id INTEGER NOT NULL, ' .
                'role_id INTEGER NOT NULL, ' .
                'PRIMARY KEY (user_id, role_id)' .
                ')',
            native: true,
        );
    }
}
