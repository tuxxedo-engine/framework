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

namespace Tuxxedo\Database\Driver\Pgsql;

use PgSql\Connection;
use PgSql\Result;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class PgsqlConnection implements ConnectionInterface
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DefaultDriver $driver;

    private Connection $pgsql;
    private readonly \Closure $connector;

    private bool $inTransaction = false;

    public function __construct(
        ConfigInterface $config,
    ) {
        $this->name = $config->getString('name');
        $this->role = $config->getEnum('role', ConnectionRole::class);
        $this->driver = DefaultDriver::PGSQL;

        $this->connector = function () use ($config): void {
            if (!isset($this->pgsql)) {
                $quote = static function (string $value): string {
                    return "'" . \addcslashes($value, "\\'") . "'";
                };

                $dsn = [];

                if ($config->isString('unixSocket')) {
                    $dsn[] = 'host=' . $quote($config->getString('unixSocket'));
                } elseif ($config->isString('host')) {
                    $dsn[] = 'host=' . $quote($config->getString('host'));
                }

                if ($config->has('port')) {
                    $dsn[] = 'port=' . $quote((string) $config->getInt('port'));
                }

                if ($config->has('database')) {
                    $dsn[] = 'dbname=' . $quote($config->getString('database'));
                }

                if ($config->has('username')) {
                    $dsn[] = 'user=' . $quote($config->getString('username'));
                }

                if ($config->has('password')) {
                    $dsn[] = 'password=' . $quote($config->getString('password'));
                }

                if ($config->has('options.timeout')) {
                    $dsn[] = 'connect_timeout=' . $quote((string) $config->getInt('options.timeout'));
                }

                if ($config->getBool('ssl.enabled')) {
                    $sslMode = $config->has('ssl.mode')
                        ? $config->getString('ssl.mode')
                        : ($config->getBool('ssl.verifyHost')
                            ? 'verify-full'
                            : (
                                $config->getBool('ssl.verifyPeer')
                                    ? 'verify-ca'
                                    : 'require'
                            ));

                    $dsn[] = 'sslmode=' . $quote($sslMode);

                    if ($config->has('ssl.ca')) {
                        $value = $config->getString('ssl.ca');

                        if ($value !== '') {
                            $dsn[] = 'sslrootcert=' . $quote($value);
                        }
                    }
                    if ($config->has('ssl.cert')) {
                        $value = $config->getString('ssl.cert');

                        if ($value !== '') {
                            $dsn[] = 'sslcert=' . $quote($value);
                        }
                    }
                    if ($config->has('ssl.key')) {
                        $value = $config->getString('ssl.key');

                        if ($value !== '') {
                            $dsn[] = 'sslkey=' . $quote($value);
                        }
                    }
                } else {
                    $dsn[] = 'sslmode=' . $quote('disable');
                }

                $pgsql = $config->getBool('options.persistent')
                    ? \pg_pconnect(\join(' ', $dsn))
                    : \pg_connect(\join(' ', $dsn));

                if ($pgsql === false) {
                    throw DatabaseException::fromCannotConnect(code: 0, error: 'Connection error');
                }

                $this->pgsql = $pgsql;

                if ($config->has('options.charset')) {
                    $value = $config->getString('options.charset');

                    if ($value !== '') {
                        $result = \pg_set_client_encoding($this->pgsql, $value);

                        if ($result !== 0) {
                            $this->throwFromLastError($this->pgsql);
                        }
                    }
                }
            }
        };

        if (!$config->getBool('options.lazy')) {
            $this->connect();
        }
    }

    private function connectCheck(): void
    {
        if (!isset($this->pgsql)) {
            $this->connect();
        }
    }

    public function throwFromLastError(
        Connection $pgsql,
    ): never {
        throw DatabaseException::fromError(
            sqlState: 'HY000',
            code: 0,
            error: \pg_last_error($pgsql),
        );
    }

    public function throwFromResult(
        Result $result,
    ): never {
        $state = \pg_result_error_field($result, \PGSQL_DIAG_SQLSTATE);
        $message = \pg_result_error($result);

        throw DatabaseException::fromError(
            sqlState: $state !== false && $state !== null
                ? $state
                : 'HY000',
            code: 0,
            error: $message !== false
                ? $message
                : 'Unknown error',
        );
    }

    public function getDriverInstance(): Connection
    {
        $this->connectCheck();

        return $this->pgsql;
    }

    public function connect(
        bool $reconnect = false,
    ): void {
        if ($reconnect || !isset($this->pgsql)) {
            ($this->connector)();
        }
    }

    public function close(): void
    {
        if (isset($this->pgsql)) {
            \pg_close($this->pgsql);

            unset($this->pgsql);
        }
    }

    public function isConnected(): bool
    {
        return isset($this->pgsql);
    }

    public function ping(): bool
    {
        try {
            $this->connectCheck();

            return \pg_query($this->pgsql, 'SELECT 1') !== false;
        } catch (\Exception) {
            return false;
        }
    }

    public function serverVersion(): string
    {
        $this->connectCheck();

        $version = \pg_parameter_status($this->pgsql, 'server_version');

        if ($version !== false) {
            return $version;
        }

        $info = \pg_version($this->pgsql);

        return (string) ($info['server'] ?? '');
    }

    public function lastInsertIdAsString(
        ?string $sequence = null,
    ): ?string {
        $this->connectCheck();

        if ($sequence !== null && $sequence !== '') {
            $result = \pg_query_params(
                $this->pgsql,
                'SELECT currval($1)',
                [
                    $sequence,
                ],
            );
        } else {
            $result = \pg_query(
                $this->pgsql,
                'SELECT lastval()',
            );
        }

        if ($result === false) {
            $this->throwFromLastError($this->pgsql);
        }

        $id = \pg_fetch_result($result, 0, 0);

        if ($id === false) {
            $this->throwFromResult($result);
        }

        return (string) $id;
    }

    public function lastInsertIdAsInt(
        ?string $sequence = null,
    ): ?int {
        return (int) $this->lastInsertIdAsString($sequence);
    }

    public function begin(): void
    {
        $this->connectCheck();
        ;

        if ($this->inTransaction) {
            throw DatabaseException::fromAlreadyInTransaction();
        }

        if (\pg_query($this->pgsql, 'BEGIN') === false) {
            $this->throwFromLastError($this->pgsql);
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        if (\pg_query($this->pgsql, 'COMMIT') === false) {
            $this->inTransaction = false;

            $this->throwFromLastError($this->pgsql);
        }

        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        if (\pg_query($this->pgsql, 'ROLLBACK') === false) {
            $this->inTransaction = false;

            $this->throwFromLastError($this->pgsql);
        }

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
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
    ): PgsqlStatement {
        $this->connectCheck();

        return new PgsqlStatement(
            connection: $this,
            sql: $sql,
        );
    }

    public function execute(
        string $sql,
        array $parameters = [],
    ): PgsqlResultSet {
        return $this->prepare($sql)->execute($parameters);
    }

    public function query(
        string $sql,
    ): PgsqlResultSet {
        $this->connectCheck();

        $result = \pg_query($this->pgsql, $sql);

        if ($result === false) {
            $this->throwFromLastError($this->pgsql);
        }

        return new PgsqlResultSet(
            result: $result,
            affectedRows: \pg_affected_rows($result),
        );
    }
}
