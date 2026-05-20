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

namespace Unit\Security\Csrf;

use PHPUnit\Framework\TestCase;
use Support\Security\Csrf\Storage\StubCsrfStorageHandler;
use Tuxxedo\Security\Csrf\CsrfManager;

class CsrfManagerTest extends TestCase
{
    public function testGetTokenReturnsStoredTokenWhenPresent(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(
                token: 'existing-token',
            ),
        );

        self::assertSame(
            'existing-token',
            $manager->getToken(),
        );
    }

    public function testGetTokenRegeneratesAndPersistsWhenStorageIsEmpty(): void
    {
        $storage = new StubCsrfStorageHandler();
        $manager = new CsrfManager(
            storage: $storage,
        );

        $token = $manager->getToken();

        self::assertNotSame('', $token);
        self::assertSame($token, $storage->token);
    }

    public function testGetTokenReturnsConsistentTokenAcrossCalls(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(),
        );

        self::assertSame(
            $manager->getToken(),
            $manager->getToken(),
        );
    }

    public function testRegenerateProducesNewTokenAndOverwritesStorage(): void
    {
        $storage = new StubCsrfStorageHandler(
            token: 'old-token',
        );

        $manager = new CsrfManager(
            storage: $storage,
        );

        $token = $manager->regenerate();

        self::assertNotSame('old-token', $token);
        self::assertSame($token, $storage->token);
    }

    public function testRegenerateReturnsSixtyFourCharacterHexToken(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(),
        );

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{64}$/',
            $manager->regenerate(),
        );
    }

    public function testRegenerateProducesDifferentTokenEachCall(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(),
        );

        self::assertNotSame(
            $manager->regenerate(),
            $manager->regenerate(),
        );
    }

    public function testValidateReturnsTrueForMatchingStoredToken(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(
                token: 'token-abc',
            ),
        );

        self::assertTrue(
            $manager->validate('token-abc'),
        );
    }

    public function testValidateReturnsFalseForMismatchedToken(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(
                token: 'token-abc',
            ),
        );

        self::assertFalse(
            $manager->validate('token-xyz'),
        );
    }

    public function testValidateReturnsFalseWhenNoTokenIsStored(): void
    {
        $manager = new CsrfManager(
            storage: new StubCsrfStorageHandler(),
        );

        self::assertFalse(
            $manager->validate('anything'),
        );
    }

    public function testClearRemovesStoredToken(): void
    {
        $storage = new StubCsrfStorageHandler(
            token: 'token-abc',
        );

        $manager = new CsrfManager(
            storage: $storage,
        );

        $manager->clear();

        self::assertNull($storage->token);
    }

    public function testGetTokenAfterClearRegeneratesNewToken(): void
    {
        $storage = new StubCsrfStorageHandler(
            token: 'original-token',
        );

        $manager = new CsrfManager(
            storage: $storage,
        );

        $manager->clear();
        $token = $manager->getToken();

        self::assertNotSame('original-token', $token);
        self::assertSame($token, $storage->token);
    }
}
