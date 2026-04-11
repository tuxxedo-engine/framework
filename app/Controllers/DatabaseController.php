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

namespace App\Controllers;

use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Http\Request\Middleware\OutputCapture;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;

#[Controller(uri: '/db/')]
readonly class DatabaseController
{
    public function __construct(
        private ConnectionManagerInterface $manager,
    ) {
    }

    #[Route\Get]
    #[OutputCapture]
    public function index(): ResponseInterface
    {
        $connection = $this->manager->getDefaultConnection();

        \var_dump($connection->isConnected());
        \var_dump($connection->ping());
        \var_dump($connection->isConnected());

        $connection->close();

        \var_dump($connection->isConnected());
        \var_dump($connection->ping());
        \var_dump($connection->isConnected());

        \var_dump($connection->serverVersion());
        \var_dump($connection->inTransaction());

        $connection->transaction(
            transaction: static function (ConnectionInterface $connection): void {
                $connection->query('SELECT 1');

                \var_dump($connection->inTransaction());
            },
        );

        \var_dump($connection->inTransaction());

        try {
            $connection->begin();

            $result = $connection->query('SELECT 1 as \'one\'');

            $connection->commit();
        } catch (DatabaseException $exception) {
            $connection->rollback();

            throw $exception;
        }

        \var_dump(\count($result));
        \var_dump($result->fetchObject()->properties['one'] ?? null);

        $connection->query('DROP TABLE IF EXISTS `test`');
        $connection->query('CREATE TABLE `test` (`a` VARCHAR(32) NOT NULL, `b` INT(8) NOT NULL)');
        $connection->query('INSERT INTO `test` VALUES ("foo", "123")');
        $connection->query(
            'INSERT INTO `test` VALUES (:a, :b)',
            [
                'a' => 'bar',
                'b' => 456,
            ],
        );

        \var_dump(
            $connection->lastInsertIdAsInt(),
            $connection->lastInsertIdAsString(),
        );

        \var_dump(
            \iterator_to_array(
                $connection->query(
                    'SELECT * FROM `test` WHERE `a` IN (:ids[])',
                    [
                    'ids' => [
                        'foo',
                        'bar',
                    ],
                ],
                )->fetchAll(),
            ),
        );

        $connection->query('DROP TABLE `test`');

        $statement = $connection->insert('users')
            ->set('name', 'Kalle')
            ->compile();

        \var_dump($statement->sql, $statement->parameters);

        $statement = $connection->insert('users')
            ->set('name', 'Kalle')
            ->set('email', 'kalle@php.net')
            ->set('active', true)
            ->set('score', 42)
            ->compile();

        \var_dump($statement->sql, $statement->parameters);

        $statement = $connection->insert('users')
            ->set('name', 'Kalle')
            ->set('deleted_at', null)
            ->compile();

        \var_dump($statement->sql, $statement->parameters);

        $statement = $connection->insert('user`data')
            ->set('col`name', 'value')
            ->compile();

        \var_dump($statement->sql, $statement->parameters);

        $statement = $connection->insert('users')
            ->set("it's a column", 'value')
            ->compile();

        \var_dump($statement->sql, $statement->parameters);

        $connection->query('DROP TABLE IF EXISTS `test_insert`');
        $connection->query('CREATE TABLE `test_insert` (`name` VARCHAR(32) NOT NULL, `score` INT NOT NULL)');

        $connection->insert('test_insert')
            ->set('name', 'Tuxxedo')
            ->set('score', 42)
            ->execute();

        \var_dump(
            \iterator_to_array(
                $connection->query('SELECT * FROM `test_insert`')->fetchAll(),
            ),
        );

        $connection->query('DROP TABLE `test_insert`');

        return Response::text('');
    }
}
