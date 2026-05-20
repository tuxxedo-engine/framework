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

namespace Unit\Security\Csrf\Storage;

use PHPUnit\Framework\TestCase;
use Support\Session\StubSessionAdapter;
use Tuxxedo\Security\Csrf\Storage\CsrfSessionStorageHandler;
use Tuxxedo\Session\Session;
use Tuxxedo\Session\SessionStartMode;

class CsrfSessionStorageHandlerTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function makeSession(
        array $data = [],
    ): Session {
        return new Session(
            adapter: new StubSessionAdapter(
                startMode: SessionStartMode::LAZY,
                data: $data,
            ),
        );
    }

    public function testGetReturnsNullWhenKeyMissing(): void
    {
        $storage = new CsrfSessionStorageHandler(
            session: $this->makeSession(),
        );

        self::assertNull($storage->get());
    }

    public function testGetReturnsNullWhenStoredValueIsEmptyString(): void
    {
        $storage = new CsrfSessionStorageHandler(
            session: $this->makeSession(
                data: [
                    '__tuxxedo_csrf_token' => '',
                ],
            ),
        );

        self::assertNull($storage->get());
    }

    public function testGetReturnsTokenWhenStored(): void
    {
        $storage = new CsrfSessionStorageHandler(
            session: $this->makeSession(
                data: [
                    '__tuxxedo_csrf_token' => 'token-abc',
                ],
            ),
        );

        self::assertSame(
            'token-abc',
            $storage->get(),
        );
    }

    public function testGetReturnsNullWhenStoredValueIsNotString(): void
    {
        $storage = new CsrfSessionStorageHandler(
            session: $this->makeSession(
                data: [
                    '__tuxxedo_csrf_token' => 42,
                ],
            ),
        );

        self::assertNull($storage->get());
    }

    public function testSetWritesTokenToSessionUnderDefaultKey(): void
    {
        $session = $this->makeSession();
        $storage = new CsrfSessionStorageHandler(
            session: $session,
        );

        $storage->set(
            token: 'token-xyz',
        );

        self::assertSame(
            'token-xyz',
            $session->getRaw('__tuxxedo_csrf_token'),
        );
    }

    public function testClearRemovesTokenFromSession(): void
    {
        $session = $this->makeSession(
            data: [
                '__tuxxedo_csrf_token' => 'token-abc',
            ],
        );

        $storage = new CsrfSessionStorageHandler(
            session: $session,
        );

        $storage->clear();

        self::assertFalse(
            $session->has('__tuxxedo_csrf_token'),
        );
    }

    public function testUsesCustomKeyWhenProvided(): void
    {
        $session = $this->makeSession();
        $storage = new CsrfSessionStorageHandler(
            session: $session,
            key: 'my_csrf',
        );

        $storage->set(
            token: 'token-abc',
        );

        self::assertSame(
            'token-abc',
            $session->getRaw('my_csrf'),
        );
    }

    public function testGetUsesCustomKeyWhenProvided(): void
    {
        $session = $this->makeSession(
            data: [
                'my_csrf' => 'token-abc',
            ],
        );

        $storage = new CsrfSessionStorageHandler(
            session: $session,
            key: 'my_csrf',
        );

        self::assertSame(
            'token-abc',
            $storage->get(),
        );
    }
}
