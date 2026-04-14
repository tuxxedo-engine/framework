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

namespace Tuxxedo\Database\Driver\Pdo;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractConnection;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

abstract class AbstractPdoConnection extends AbstractConnection
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver|string $driver;
    public readonly DialectInterface $dialect;

    protected private(set) \PDO $pdo;
    private readonly \Closure $connector;

    protected readonly StatementParserInterface $statementParser;

    final protected function __construct(
        private readonly ContainerInterface $container,
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = static::getDriverName();
        $this->dialect = static::getDriverDialect();
        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            try {
                $this->pdo = new \PDO(
                    dsn: static::getDsn($config),
                    username: $config->getString('username'),
                    password: $config->getString('password'),
                    options: static::getPdoOptions($config) + [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_PERSISTENT => $config->getBool('options.persistent'),
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
     * @return array<\PDO::ATTR_*|\PDO::*_ATTR_*, mixed>
     */
    protected function getPdoOptions(
        ConfigInterface $config,
    ): array {
        return [];
    }

    abstract protected function getDriverName(): DefaultDriver|string;

    abstract protected function getDriverDialect(): DialectInterface;

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

    public function lastInsertIdAsString(): ?string
    {
        $this->connectCheck();

        try {
            $id = $this->pdo->lastInsertId();

            if ($id === false) {
                $this->throwFromErrorInfo();
            }
        } catch (\PDOException $exception) {
            $this->throwFromPdoException($exception);
        }

        if ($id !== '' && $id !== '0') {
            return $id;
        }

        return null;
    }

    public function lastInsertIdAsInt(): ?int
    {
        $this->connectCheck();

        try {
            $id = $this->pdo->lastInsertId();

            if ($id === false) {
                $this->throwFromErrorInfo();
            }
        } catch (\PDOException $exception) {
            $this->throwFromPdoException($exception);
        }


        if ($id !== '' && $id !== '0') {
            return (int) $id;
        }

        return null;
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

    public function query(
        string $sql,
        array $parameters = [],
        bool $native = false,
    ): PdoResultSet {
        $this->connectCheck();

        if (!$native) {
            $parsedStatement = $this->statementParser->parse($sql, $parameters);
            $sql = $parsedStatement->sql;
            $parameters = $parsedStatement->parameters;
        }

        $statement = $this->pdo->prepare($sql);

        if ($statement === false) {
            $this->throwFromErrorInfo();
        }

        foreach ($parameters as $index => $value) {
            if (\is_array($value)) {
                continue;
            }

            $bound = $statement->bindValue(
                param: !$native
                    ? $index + 1
                    : $index,
                value: $value,
                type: match (true) {
                    \is_int($value) => \PDO::PARAM_INT,
                    \is_bool($value) => \PDO::PARAM_BOOL,
                    \is_null($value) => \PDO::PARAM_NULL,
                    default => \PDO::PARAM_STR,
                },
            );

            if (!$bound) {
                $this->throwFromErrorInfo(
                    statement: $statement,
                );
            }
        }

        if (!$statement->execute()) {
            $this->throwFromErrorInfo(
                statement: $statement,
            );
        }

        if ($statement->columnCount() > 0) {
            return new PdoResultSet(
                container: $this->container,
                result: $statement,
                affectedRows: 0,
            );
        }

        return new PdoResultSet(
            container: $this->container,
            result: null,
            affectedRows: $statement->rowCount(),
        );
    }
}
