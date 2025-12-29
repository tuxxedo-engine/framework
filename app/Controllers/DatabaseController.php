<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\OutputCaptureMiddleware;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
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
    #[OutputCaptureMiddleware]
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

        $connection->query('CREATE TABLE `test` ( `a` VARCHAR(32) NOT NULL, `b` VARCHAR(32) NOT NULL )');
        $connection->query('INSERT INTO `test` VALUES ("foo", "bar")');
        $connection->query('DROP TABLE `test`');

        \var_dump(
            $connection->lastInsertIdAsInt(),
            $connection->lastInsertIdAsString(),
        );

        $connection->query('DROP TABLE `test`');

        return new Response();
    }
}
