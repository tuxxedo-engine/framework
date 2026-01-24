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

namespace Tuxxedo\Database\Driver\Pdo;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

abstract class AbstractPdoConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver|string $driver;
    protected private(set) \PDO $pdo;
    private readonly \Closure $connector;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = static::getDriverName();
        $this->connector = function () use ($config): void {
            try {
                $this->pdo = new \PDO(
                    dsn: static::getDsn($config),
                    username: $config->getString('username'),
                    password: $config->getString('password'),
                    options: [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_EMULATE_PREPARES => true,
                        \PDO::ATTR_PERSISTENT => $config->getBool('options.persistent'),
                        ...static::getPdoOptions($config),
                    ],
                );

                $this->postConnectHook($config);
            } catch (\PDOException $exception) {
                $this->throwFromPdoException($exception);
            }
        };

        if (!$config->getBool('options.lazy')) {
            $this->connect();
        }
    }

    protected function postConnectHook(
        ConfigInterface $config,
    ): void {
    }

    /**
     * @return array<\PDO::ATTR_*, mixed>
     */
    protected function getPdoOptions(
        ConfigInterface $config,
    ): array {
        return [];
    }

    abstract protected function getDriverName(): DefaultDriver|string;

    abstract protected function getDsn(
        ConfigInterface $config,
    ): string;

    /**
     * @throws DatabaseException
     */
    private function connectCheck(): void
    {
        if (!isset($this->pdo)) {
            $this->connect();
        }
    }

    /**
     * @throws DatabaseException
     */
    public function throwFromPdoException(
        \PDOException $exception,
    ): never {
        /** @var array{0: string, 1: string|int, 2: string} $errorInfo */
        $errorInfo = $exception->errorInfo ?? [
            'HY000',
            $exception->getCode(),
            $exception->getMessage(),
        ];

        throw DatabaseException::fromError(
            sqlState: $errorInfo[0],
            code: $errorInfo[1],
            error: $errorInfo[2],
        );
    }

    public function throwFromErrorInfo(
        ?\PDOStatement $statement = null,
    ): never {
        /** @var array{0: string, 1: string|int, 2: string} $errorInfo */
        $errorInfo = $statement?->errorInfo() ?? $this->pdo->errorInfo();

        throw DatabaseException::fromError(
            sqlState: $errorInfo[0],
            code: $errorInfo[1],
            error: $errorInfo[2],
        );
    }

    public function getDriverInstance(): \PDO
    {
        $this->connectCheck();

        return $this->pdo;
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        if ($reconnect || !isset($this->pdo)) {
            ($this->connector)();
        }
    }

    public function close(): void
    {
        unset($this->pdo);
    }

    public function isConnected(): bool
    {
        return isset($this->pdo);
    }

    public function ping(): bool
    {
        try {
            $this->connectCheck();

            $this->pdo->query('SELECT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function serverVersion(): string
    {
        $this->connectCheck();

        /** @var string */
        return $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    public function lastInsertIdAsString(
        ?string $sequence = null,
    ): ?string {
        $this->connectCheck();

        try {
            $id = $this->pdo->lastInsertId($sequence);

            if ($id === false) {
                $this->throwFromErrorInfo();
            }
        } catch (\PDOException $exception) {
            $this->throwFromPdoException($exception);
        }

        return $id;
    }

    public function lastInsertIdAsInt(
        ?string $sequence = null,
    ): ?int {
        $this->connectCheck();

        try {
            $id = $this->pdo->lastInsertId($sequence);

            if ($id === false) {
                $this->throwFromErrorInfo();
            }
        } catch (\PDOException $exception) {
            $this->throwFromPdoException($exception);
        }

        return (int) $id;
    }

    public function begin(): void
    {
        $this->connectCheck();

        if (!$this->pdo->beginTransaction()) {
            $this->throwFromErrorInfo();
        }
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->pdo->commit()) {
            $this->throwFromErrorInfo();
        }
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->pdo->rollBack()) {
            $this->throwFromErrorInfo();
        }
    }

    public function inTransaction(): bool
    {
        $this->connectCheck();

        return $this->pdo->inTransaction();
    }

    public function transaction(
        \Closure $transaction,
    ): void {
        try {
            $this->begin();

            $transaction($this);

            $this->commit();
        } catch (\Exception $exception) {
            $this->rollback();

            throw $exception;
        }
    }

    public function prepare(
        string $sql,
    ): PdoStatement {
        $this->connectCheck();

        return new PdoStatement(
            connection: $this,
            sql: $sql,
        );
    }

    public function execute(
        string $sql,
        array $parameters = [],
    ): PdoResultSet {
        return $this->prepare($sql)->execute($parameters);
    }

    public function query(
        string $sql,
    ): PdoResultSet {
        $this->connectCheck();

        try {
            $result = $this->pdo->query($sql);

            if ($result === false) {
                $this->throwFromErrorInfo();
            }

            return new PdoResultSet(
                result: $result,
                affectedRows: $result->rowCount(),
            );
        } catch (\PDOException $exception) {
            $this->throwFromPdoException($exception);
        }
    }
}
