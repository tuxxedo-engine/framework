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

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

return [
    /**
     * database.default.name
     *
     * Name of this database connection, this is useful if the application registers multiple
     * connections of the same database driver to differentiate between them
     *
     * @type string
     */
    'name' => '',

    /**
     * database.default.role
     *
     * Role of this connection. A role dictates how it is accessible via the connection manager
     * object:
     *
     * ConnectionRole::NONE             - For named connections that doesn't have a specific role
     * ConnectionRole::DEFAULT          - For connections that acts as the default read and write connection
     * ConnectionRole::DEFAULT_READ     - For connections that acts as the default read connection if using multiples
     * ConnectionRole::DEFAULT_WRITE    - For connections that acts as the default write connection if using multiples
     *
     * Note, if multiple are defined of the same role, then functionality that fetches a connection by role is
     * undefined. To avoid this, set extra connections as ConnectionRole::NONE or use named connections instead.
     *
     * @type ConnectionRole
     */
    'role' => ConnectionRole::DEFAULT,

    /**
     * database.default.driver
     *
     * Driver of the connection, this directive should only be set if the application is using a
     * driver that comes with Tuxxedo Engine by default:
     *
     * DefaultDriver::MYSQL             - For mysql connections using the mysqli PHP extension
     * DefaultDriver::PDO               - For generic PDO drivers that uses a custom DSN
     * DefaultDriver::PDO_MYSQL         - For mysql connections using the PDO mysql PHP extension
     * DefaultDriver::PDO_PGSQL         - For postgres connections using the PDO pgsql PHP extension
     * DefaultDriver::PDO_SQLITE        - For sqlite connections using the PDO sqlite PHP extension
     * DefaultDriver::PGSQL             - For postgres connections using the pgsql PHP extension
     * DefaultDriver::SQLITE            - For sqlite connections using the sqlite3 PHP extension
     *
     * If a custom connection is used, then this should be commented out, with a class set database.default.class
     *
     * @type DefaultDriver
     */
    'driver' => DefaultDriver::PDO_SQLITE,

    /**
     * database.default.class
     *
     * Custom database driver class, this is used if a third party Tuxxedo Engine compatible database driver
     * is used
     *
     * @type class-string<ConnectionInterface>
     */
    'class' => '',

    /**
     * database.default.dsn
     *
     * Custom DSN for drivers that support those, this will take priority of other configuration directives
     * which is dependent on the driver, the following default drivers support DSNs:
     *
     * DefaultDriver::PDO
     * DefaultDriver::PDO_MYSQL
     * DefaultDriver::PDO_PGSQL
     * DefaultDriver::PDO_SQLITE
     * DefaultDriver::PGSQL
     *
     * For DefaultDriver::PDO, this value is always required
     *
     * @type string
     */
    'dsn' => '',

    /**
     * database.default.host
     *
     * Host name or IP address of the host where the database server is located
     *
     * DefaultDriver::MYSQL                     - This value is ignored if a unix socket is used
     * DefaultDriver::PDO_SQLITE                - This value has no effect
     * DefaultDriver::SQLITE                    - This value has no effect
     *
     * @type string
     */
    'host' => 'localhost',

    /**
     * database.default.port
     *
     * Port number of the host where the database server is located
     *
     * DefaultDriver::MYSQL                     - This value is ignored if a unix socket is used
     * DefaultDriver::PDO_SQLITE                - This value has no effect
     * DefaultDriver::SQLITE                    - This value has no effect
     *
     * If the default port is used, then this should be commented out
     *
     * @type int
     */
    'port' => 3306,

    /**
     * database.default.unixSocket
     *
     * Unix socket of where the database server is located, this will override the
     * database.default.host and database.default.port if used
     *
     * This value is only supported by DefaultDriver::MYSQL and is only active if
     * it has a value
     *
     * @type string
     */
    'unixSocket' => '',

    /**
     * database.default.username
     *
     * The username used to authenticate with the database server
     *
     * DefaultDriver::PDO_SQLITE                - This value has no effect
     * DefaultDriver::SQLITE                    - This value has no effect
     *
     * @type string
     */
    'username' => 'root',

    /**
     * database.default.password
     *
     * The password used to authenticate with the database server
     *
     * DefaultDriver::PDO_SQLITE                - This value has no effect
     * DefaultDriver::SQLITE                    - This value is used for the encryption key when opening a database file
     *
     * @type string
     */
    'password' => '',

    /**
     * database.default.database
     *
     * The database name where this connection should connect to by default
     *
     * DefaultDriver::PDO_SQLITE                - This value must be a path to the sqlite database file
     * DefaultDriver::SQLITE                    - This value must be a path to the sqlite database file
     *
     * Note, this can be empty if the connection will not operate on a specific
     * database
     *
     * @type string
     */
    'database' => '',

    /**
     * database.default.options
     *
     * Generic connection options for a driver
     *
     * @type array<mixed>
     */
    'options' => [
        /**
         * database.default.options.charset
         *
         * The character set for the connection if any
         *
         * The value here may differ depending on the database driver, for example mysql
         * based drivers may use utf8mb4 whereas others may use UTF8. Tuxxedo Engine assumes
         * user input is UTF-8 by default, an application may use the mbstring PHP extension
         * to convert between character sets before passing it on to the database
         *
         * Mysql typically uses 'utf8mb4' and postgres uses 'UTF8'
         *
         * DefaultDriver::SQLITE                    - This value has no effect
         *
         * @type string
         */
        'charset' => 'utf8mb4',

        /**
         * database.default.options.persistent
         *
         * Whether the connection once established should be persistent
         *
         * DefaultDriver::PDO_SQLITE                - This value has no effect
         * DefaultDriver::SQLITE                    - This value has no effect
         *
         * Generic PDO connections will use this flag if supported. Mysql connections
         * that is compiled with libmysql may not support this value and will simply
         * be ignored
         *
         * @type bool
         */
        'persistent' => false,

        /**
         * database.default.options.lazy
         *
         * Whether this connection should be lazily initialized, if this is set to false
         * then a connection is attempted to be established when the driver is registered,
         * otherwise a connection is only established once an action that demands an active
         * connection is used
         *
         * @type bool
         */
        'lazy' => true,

        /**
         * database.default.options.timeout
         *
         * The timeout in seconds for connections and queries. The exact meaning may
         * depend on the driver in question
         *
         * DefaultDriver::SQLITE                    - This value has no effect
         *
         * @type int
         */
        'timeout' => 3,

        /**
         * database.default.options.flags
         *
         * The open flags for a database, this may vary depending on the driver
         *
         * DefaultDriver::SQLITE                    - Bitfield of \SQLITE3_OPEN_* constants used when opening the database file
         *
         * If this value is NULL, then the driver defaults is used
         *
         * @type int|null
         */
        'flags' => null,
    ],

    /**
     * database.default.ssl
     *
     * SSL options for the database connection
     *
     * DefaultDriver::PDO_SQLITE                - This section has no effect
     * DefaultDriver::SQLITE                    - This section has no effect
     *
     * @type array<mixed>
     */
    'ssl' => [
        /**
         * database.default.ssl.enabled
         *
         * Whether SSL should be used for this connection or not
         *
         * @type bool
         */
        'enabled' => false,

        /**
         * database.default.ssl.mode
         *
         * The SSL mode for this database connection. This value depends on the database
         * server system
         *
         * DefaultDriver::MYSQL                 - This value has no effect
         *
         * @type string
         */
        'mode' => '',

        /**
         * database.default.ssl.ca
         *
         * The path to the SSL certificate used to verify the server certificate chain
         *
         * @type string
         */
        'ca' => '',

        /**
         * database.default.ssl.cert
         *
         * Path to the client certificate used for mutual TLS (mTLS)
         *
         * @type string
         */
        'cert' => '',

        /**
         * database.default.ssl.key
         *
         * The path to the private key that pairs with database.default.ssl.cert
         *
         * @type string
         */
        'key' => '',

        /**
         * database.default.ssl.verifyPeer
         *
         * Whether to verify the server certificate from database.default.ssl.ca
         *
         * @type bool
         */
        'verifyPeer' => true,

        /**
         * database.default.ssl.verifyHost
         *
         * Whether to verify the host certificate matches the targeted host
         *
         * DefaultDriver::MYSQL                 - This value has no effect
         *
         * @type bool
         */
        'verifyHost' => true,
    ],
];
