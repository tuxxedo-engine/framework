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

namespace Support\Security\Jwt;

class OpensslKeyComponents
{
    /**
     * @return array{n: string, e: string}
     */
    public static function rsaPublic(
        string $pem,
    ): array {
        $handle = \openssl_pkey_get_public($pem);

        if ($handle === false) {
            throw new \RuntimeException(
                message: 'Fixture RSA public PEM did not parse',
            );
        }

        $rsa = self::sectionFromHandle(
            handle: $handle,
            key: 'rsa',
        );

        return [
            'n' => self::stringField(
                data: $rsa,
                key: 'n',
            ),
            'e' => self::stringField(
                data: $rsa,
                key: 'e',
            ),
        ];
    }

    /**
     * @return array{n: string, e: string, d: string, p: string, q: string, dmp1: string, dmq1: string, iqmp: string}
     */
    public static function rsaPrivate(
        string $pem,
    ): array {
        $handle = \openssl_pkey_get_private($pem);

        if ($handle === false) {
            throw new \RuntimeException(
                message: 'Fixture RSA private PEM did not parse',
            );
        }

        $rsa = self::sectionFromHandle(
            handle: $handle,
            key: 'rsa',
        );

        return [
            'n' => self::stringField(
                data: $rsa,
                key: 'n',
            ),
            'e' => self::stringField(
                data: $rsa,
                key: 'e',
            ),
            'd' => self::stringField(
                data: $rsa,
                key: 'd',
            ),
            'p' => self::stringField(
                data: $rsa,
                key: 'p',
            ),
            'q' => self::stringField(
                data: $rsa,
                key: 'q',
            ),
            'dmp1' => self::stringField(
                data: $rsa,
                key: 'dmp1',
            ),
            'dmq1' => self::stringField(
                data: $rsa,
                key: 'dmq1',
            ),
            'iqmp' => self::stringField(
                data: $rsa,
                key: 'iqmp',
            ),
        ];
    }

    /**
     * @return array{x: string, y: string}
     */
    public static function ecPublic(
        string $pem,
    ): array {
        $handle = \openssl_pkey_get_public($pem);

        if ($handle === false) {
            throw new \RuntimeException(
                message: 'Fixture EC public PEM did not parse',
            );
        }

        $ec = self::sectionFromHandle(
            handle: $handle,
            key: 'ec',
        );

        return [
            'x' => self::stringField(
                data: $ec,
                key: 'x',
            ),
            'y' => self::stringField(
                data: $ec,
                key: 'y',
            ),
        ];
    }

    /**
     * @return array{x: string, y: string, d: string}
     */
    public static function ecPrivate(
        string $pem,
    ): array {
        $handle = \openssl_pkey_get_private($pem);

        if ($handle === false) {
            throw new \RuntimeException(
                message: 'Fixture EC private PEM did not parse',
            );
        }

        $ec = self::sectionFromHandle(
            handle: $handle,
            key: 'ec',
        );

        return [
            'x' => self::stringField(
                data: $ec,
                key: 'x',
            ),
            'y' => self::stringField(
                data: $ec,
                key: 'y',
            ),
            'd' => self::stringField(
                data: $ec,
                key: 'd',
            ),
        ];
    }

    /**
     * @return array<mixed, mixed>
     */
    private static function sectionFromHandle(
        \OpenSSLAsymmetricKey $handle,
        string $key,
    ): array {
        $details = \openssl_pkey_get_details($handle);

        if ($details === false) {
            throw new \RuntimeException(
                message: 'openssl_pkey_get_details returned false',
            );
        }

        $section = $details[$key] ?? null;

        if (!\is_array($section)) {
            throw new \RuntimeException(
                message: \sprintf(
                    'openssl_pkey_get_details returned no "%s" section',
                    $key,
                ),
            );
        }

        return $section;
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private static function stringField(
        array $data,
        string $key,
    ): string {
        $value = $data[$key] ?? null;

        if (!\is_string($value)) {
            throw new \RuntimeException(
                message: \sprintf(
                    'openssl_pkey_get_details field "%s" was not a string',
                    $key,
                ),
            );
        }

        return $value;
    }
}
