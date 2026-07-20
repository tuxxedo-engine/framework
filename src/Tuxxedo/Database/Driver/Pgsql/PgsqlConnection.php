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

namespace Tuxxedo\Database\Driver\Pgsql;

use PgSql\Connection;
use PgSql\Result;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\AbstractConnection;
use Tuxxedo\Database\Driver\Pgsql\Config\PgsqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\PgsqlDialect;
use Tuxxedo\Database\Query\Parser\StatementParser;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

class PgsqlConnection extends AbstractConnection
{
    public readonly string $name;
    public readonly ConnectionRole $role;
    public readonly DialectInterface $dialect;

    private Connection $pgsql;
    private readonly \Closure $connector;
    private bool $inTransaction = false;

    public readonly StatementParserInterface $statementParser;

    private function __construct(
        private readonly ContainerInterface $container,
        PgsqlConnectionConfigInterface $config,
    ) {
        $this->name = $config->name;
        $this->role = $config->role;
        $this->dialect = new PgsqlDialect(
            connection: fn (): Connection => $this->getDriverInstance(),
        );

        $this->statementParser = new StatementParser(
            dialect: $this->dialect,
        );

        $this->connector = function () use ($config): void {
            if (!isset($this->pgsql)) {
                $quote = static function (string $value): string {
                    return "'" . \addcslashes($value, "\\'") . "'";
                };

                $dsn = [];

                if ($config->unixSocket !== null) {
                    // @codeCoverageIgnoreStart
                    $dsn[] = 'host=' . $quote($config->unixSocket);
                    // @codeCoverageIgnoreEnd
                } elseif ($config->host !== '') {
                    $dsn[] = 'host=' . $quote($config->host);
                }

                if ($config->port !== null) {
                    $dsn[] = 'port=' . $quote((string) $config->port);
                }

                if ($config->database !== '') {
                    $dsn[] = 'dbname=' . $quote($config->database);
                }

                if ($config->username !== '') {
                    $dsn[] = 'user=' . $quote($config->username);
                }

                if ($config->password !== '') {
                    $dsn[] = 'password=' . $quote($config->password);
                }

                if ($config->timeout !== null) {
                    $dsn[] = 'connect_timeout=' . $quote((string) $config->timeout);
                }

                if ($config->sslEnabled) {
                    // @codeCoverageIgnoreStart
                    $sslMode = $config->sslMode !== ''
                        ? $config->sslMode
                        : ($config->sslVerifyHost
                            ? 'verify-full'
                            : (
                                $config->sslVerifyPeer
                                    ? 'verify-ca'
                                    : 'require'
                            ));

                    $dsn[] = 'sslmode=' . $quote($sslMode);

                    if ($config->sslCa !== '') {
                        $dsn[] = 'sslrootcert=' . $quote($config->sslCa);
                    }

                    if ($config->sslCert !== '') {
                        $dsn[] = 'sslcert=' . $quote($config->sslCert);
                    }

                    if ($config->sslKey !== '') {
                        $dsn[] = 'sslkey=' . $quote($config->sslKey);
                    }
                    // @codeCoverageIgnoreEnd
                } else {
                    $dsn[] = 'sslmode=' . $quote('disable');
                }

                $pgsql = $config->persistent
                    ? @\pg_pconnect(\join(' ', $dsn))
                    : @\pg_connect(\join(' ', $dsn));

                if ($pgsql === false) {
                    $lastError = \error_get_last();

                    throw DatabaseException::fromCannotConnect(
                        code: 0,
                        error: $lastError['message'] ?? 'Connection error',
                    );
                }

                $this->pgsql = $pgsql;

                if ($config->charset !== '') {
                    $result = @\pg_set_client_encoding($this->pgsql, $config->charset);

                    if ($result !== 0) {
                        $this->throwFromLastError($this->pgsql); // @codeCoverageIgnore
                    }
                }
            }
        };

        if (!$config->lazy) {
            $this->connect();
        }
    }

    public static function create(
        ContainerInterface $container,
        ConnectionConfigInterface $config,
    ): self {
        /** @var PgsqlConnectionConfigInterface $config */

        return new self($container, $config);
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

    /**
     * @codeCoverageIgnore
     */
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
        if ($reconnect && isset($this->pgsql)) {
            \pg_close($this->pgsql);

            unset($this->pgsql);
        }

        if (!isset($this->pgsql)) {
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

            return @\pg_query($this->pgsql, 'SELECT 1') !== false;
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

        // @codeCoverageIgnoreStart
        $info = \pg_version($this->pgsql);

        return (string) ($info['server'] ?? '');
        // @codeCoverageIgnoreEnd
    }

    public function lastInsertIdAsString(): ?string
    {
        $this->connectCheck();

        $result = @\pg_query(
            $this->pgsql,
            'SELECT lastval()',
        );

        if (!$result instanceof Result) {
            if (\str_contains(\pg_last_error($this->pgsql), 'lastval is not yet defined')) {
                return null;
            }

            $this->throwFromLastError($this->pgsql); // @codeCoverageIgnore
        }

        $id = \pg_fetch_result($result, 0, 0);

        if ($id === false) {
            $this->throwFromResult($result); // @codeCoverageIgnore
        }

        if ($id !== '' && $id !== '0') {
            return $id;
        }

        return null; // @codeCoverageIgnore
    }

    public function lastInsertIdAsInt(): ?int
    {
        return ($id = $this->lastInsertIdAsString()) !== null
            ? (int) $id
            : null;
    }

    public function begin(): void
    {
        $this->connectCheck();
        ;

        if ($this->inTransaction) {
            throw DatabaseException::fromAlreadyInTransaction();
        }

        if (@\pg_query($this->pgsql, 'BEGIN') === false) {
            $this->throwFromLastError($this->pgsql); // @codeCoverageIgnore
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        if (@\pg_query($this->pgsql, 'COMMIT') === false) {
            // @codeCoverageIgnoreStart
            $this->inTransaction = false;

            $this->throwFromLastError($this->pgsql);
            // @codeCoverageIgnoreEnd
        }

        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        $this->connectCheck();

        if (!$this->inTransaction) {
            throw DatabaseException::fromNotInTransaction();
        }

        if (@\pg_query($this->pgsql, 'ROLLBACK') === false) {
            // @codeCoverageIgnoreStart
            $this->inTransaction = false;

            $this->throwFromLastError($this->pgsql);
            // @codeCoverageIgnoreEnd
        }

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function query(
        string $sql,
        array $parameters = [],
        bool $native = false,
    ): PgsqlResultSet {
        $this->connectCheck();

        $params = [];

        if (!$native) {
            $parsedStatement = $this->statementParser->parse($sql, $parameters);
            $sql = $parsedStatement->sql;
            $parameters = $parsedStatement->parameters;
        }

        foreach ($parameters as $value) {
            if (\is_array($value)) {
                continue;
            }

            $params[] = match (true) {
                \is_int($value) => (string) $value,
                \is_float($value) => (string) $value,
                \is_bool($value) => (string) (int) $value,
                \is_null($value) => null,
                default => $value,
            };
        }

        $result = @\pg_query_params(
            $this->pgsql,
            $sql,
            $params,
        );

        if ($result === false) {
            $this->throwFromLastError($this->pgsql);
        }

        return new PgsqlResultSet(
            container: $this->container,
            result: $result,
            affectedRows: \pg_affected_rows($result),
        );
    }
}
