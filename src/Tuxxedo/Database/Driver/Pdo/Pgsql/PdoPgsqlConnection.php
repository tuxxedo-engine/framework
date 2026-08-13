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

namespace Tuxxedo\Database\Driver\Pdo\Pgsql;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Config\ConnectionConfigInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Driver\Pdo\Pgsql\Config\PdoPgsqlConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\PgsqlDialect;

class PdoPgsqlConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        ConnectionConfigInterface $config,
    ): self {
        /** @var PdoPgsqlConnectionConfigInterface $config */

        return new self($container, $config);
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new PgsqlDialect(
            usePositionalPlaceholders: true,
        );
    }

    protected function getDsn(
        PdoConnectionConfigInterface $config,
    ): string {
        /** @var PdoPgsqlConnectionConfigInterface $config */

        if ($config->dsn !== '') {
            return $config->dsn;
        }

        $database = '';
        $port = '';
        $timeout = '';
        $sslMode = '';
        $sslParams = '';

        if ($this->currentDatabase !== '') {
            $database = ';dbname=' . $this->currentDatabase;
        }

        if ($config->port !== null) {
            $port = ';port=' . $config->port;
        }

        if ($config->timeout !== null) {
            $timeout = ';connect_timeout=' . $config->timeout;
        }

        if ($config->sslEnabled) {
            // @codeCoverageIgnoreStart
            $sslMode = ';sslmode=' . ($config->sslMode !== '' ? $config->sslMode : 'require');

            if ($config->sslCa !== '') {
                $sslParams .= ';sslrootcert=' . $config->sslCa;
            }

            if ($config->sslCert !== '') {
                $sslParams .= ';sslcert=' . $config->sslCert;
            }

            if ($config->sslKey !== '') {
                $sslParams .= ';sslkey=' . $config->sslKey;
            }
            // @codeCoverageIgnoreEnd
        } elseif ($config->sslMode !== '') {
            $sslMode = ';sslmode=' . $config->sslMode; // @codeCoverageIgnore
        }

        return \sprintf(
            'pgsql:host=%s%s%s%s%s%s',
            $config->host,
            $port,
            $database,
            $timeout,
            $sslMode,
            $sslParams,
        );
    }

    /**
     * @throws DatabaseException
     */
    protected function postConnectHook(
        PdoConnectionConfigInterface $config,
    ): void {
        /** @var PdoPgsqlConnectionConfigInterface $config */

        if ($config->charset === '') {
            return;
        }

        if (\preg_match('/\A[A-Za-z0-9_-]+\z/', $config->charset) !== 1) {
            throw DatabaseException::fromInvalidCharset(
                charset: $config->charset,
            );
        }

        try {
            $this->pdo->exec(
                \sprintf(
                    'SET client_encoding TO \'%s\'',
                    $config->charset,
                ),
            );
        } catch (\PDOException $exception) { // @codeCoverageIgnore
            self::throwFromPdoException($exception); // @codeCoverageIgnore
        }
    }

    /**
     * @return array<\PDO::ATTR_*|\PDO::*_ATTR_*, mixed>
     */
    protected function getPdoOptions(
        PdoConnectionConfigInterface $config,
    ): array {
        return [];
    }

    public function lastInsertIdAsString(): ?string
    {
        try {
            return parent::lastInsertIdAsString();
        } catch (DatabaseException $exception) {
            if (\str_contains($exception->getMessage(), 'lastval is not yet defined')) {
                return null;
            }

            throw $exception; // @codeCoverageIgnore
        }
    }

    public function lastInsertIdAsInt(): ?int
    {
        $id = $this->lastInsertIdAsString();

        return $id !== null
            ? (int) $id
            : null;
    }

    public function switchDatabase(
        string $database,
    ): void {
        $this->connectCheck();

        if ($this->isServerInTransaction()) {
            throw DatabaseException::fromCannotSwitchDatabaseInTransaction();
        }

        $previous = $this->currentDatabase;
        $this->currentDatabase = $database;

        try {
            $this->connect(
                reconnect: true,
            );
        } catch (DatabaseException $exception) {
            $this->currentDatabase = $previous;

            throw $exception;
        }
    }

    public function currentDatabase(): string
    {
        $this->connectCheck();

        $statement = $this->pdo->query('SELECT current_database()');

        if ($statement === false) {
            return $this->currentDatabase; // @codeCoverageIgnore
        }

        /** @var array{0: string|null}|false $row */
        $row = $statement->fetch(\PDO::FETCH_NUM);

        if ($row === false || $row[0] === null) {
            return ''; // @codeCoverageIgnore
        }

        return $row[0];
    }
}
