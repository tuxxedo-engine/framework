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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

abstract class AbstractPdoConnection extends AbstractConnection
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DialectInterface $dialect;

    protected private(set) \PDO $pdo;
    private readonly \Closure $connector;

    public readonly StatementParserInterface $statementParser;

    final protected function __construct(
        private readonly ContainerInterface $container,
        PdoConnectionConfigInterface $config,
    ) {
        $this->name = $config->name;
        $this->role = $config->role;
        $this->dialect = static::getDriverDialect();
        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            try {
                $this->pdo = new \PDO(
                    dsn: static::getDsn($config),
                    username: $config->username,
                    password: $config->password,
                    options: static::getPdoOptions($config) + [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_PERSISTENT => $config->persistent,
                    ],
                );

                $this->postConnectHook($config);
            } catch (\PDOException $exception) {
                self::throwFromPdoException($exception);
            }
        };

        if (!$config->lazy) {
            $this->connect();
        }
    }

    protected function postConnectHook(
        PdoConnectionConfigInterface $config,
    ): void {
    }

    /**
     * @return array<\PDO::ATTR_*|\PDO::*_ATTR_*, mixed>
     */
    abstract protected function getPdoOptions(
        PdoConnectionConfigInterface $config,
    ): array;

    abstract protected function getDriverDialect(): DialectInterface;

    abstract protected function getDsn(
        PdoConnectionConfigInterface $config,
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
    public static function throwFromPdoException(
        \PDOException $exception,
    ): never {
        if ($exception->errorInfo !== null) {
            /** @var array{0: string, 1: string|int, 2: string} $errorInfo */
            $errorInfo = $exception->errorInfo;
        } else {
            $errorInfo = [
                'HY000',
                $exception->getCode(),
                $exception->getMessage(),
            ];
        }

        throw DatabaseException::fromError(
            sqlState: $errorInfo[0],
            code: $errorInfo[1],
            error: $errorInfo[2],
        );
    }

    /**
     * @codeCoverageIgnore
     */
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
        } catch (\Exception) { // @codeCoverageIgnore
            return false; // @codeCoverageIgnore
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
                $this->throwFromErrorInfo(); // @codeCoverageIgnore
            }
        } catch (\PDOException $exception) { // @codeCoverageIgnore
            self::throwFromPdoException($exception); // @codeCoverageIgnore
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
                $this->throwFromErrorInfo(); // @codeCoverageIgnore
            }
        } catch (\PDOException $exception) { // @codeCoverageIgnore
            self::throwFromPdoException($exception); // @codeCoverageIgnore
        }


        if ($id !== '' && $id !== '0') {
            return (int) $id;
        }

        return null;
    }

    public function begin(): void
    {
        $this->connectCheck();

        if ($this->pdo->inTransaction()) {
            throw DatabaseException::fromAlreadyInTransaction();
        }

        try {
            $this->pdo->beginTransaction();
        } catch (\PDOException $exception) { // @codeCoverageIgnore
            self::throwFromPdoException($exception); // @codeCoverageIgnore
        }
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->pdo->inTransaction()) {
            throw DatabaseException::fromNotInTransaction();
        }

        try {
            $this->pdo->commit();
        } catch (\PDOException $exception) { // @codeCoverageIgnore
            self::throwFromPdoException($exception); // @codeCoverageIgnore
        }
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->pdo->inTransaction()) {
            throw DatabaseException::fromNotInTransaction();
        }

        try {
            $this->pdo->rollBack();
        } catch (\PDOException $exception) { // @codeCoverageIgnore
            self::throwFromPdoException($exception); // @codeCoverageIgnore
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

        try {
            $statement = $this->pdo->prepare($sql);
        } catch (\PDOException $exception) {
            self::throwFromPdoException($exception);
        }

        if ($statement === false) {
            $this->throwFromErrorInfo(); // @codeCoverageIgnore
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
                // @codeCoverageIgnoreStart
                $this->throwFromErrorInfo(
                    statement: $statement,
                );
                // @codeCoverageIgnoreEnd
            }
        }

        try {
            $statement->execute();
        } catch (\PDOException $exception) {
            self::throwFromPdoException($exception);
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
