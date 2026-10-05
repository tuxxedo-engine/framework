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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\Session\StubSessionAdapter;
use Tuxxedo\Session\Session;
use Tuxxedo\Session\SessionStartMode;
use Tuxxedo\View\Lumi\Library\Standard\Function\SessionFunctions;

class SessionFunctionsTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function makeFunctions(
        array $data = [],
        string $identifier = 'sess',
    ): SessionFunctions {
        return new SessionFunctions(
            session: new Session(
                adapter: new StubSessionAdapter(
                    startMode: SessionStartMode::LAZY,
                    identifier: $identifier,
                    data: $data,
                ),
            ),
        );
    }

    public function testSessionReturnsRawValueForGivenKey(): void
    {
        self::assertSame(
            'alice',
            $this->makeFunctions(
                data: [
                    'user' => 'alice',
                ],
            )->session('user'),
        );
    }

    public function testSessionReturnsNullForMissingKey(): void
    {
        self::assertNull($this->makeFunctions()->session('missing'));
    }

    public function testSessionReturnsNonStringValueVerbatim(): void
    {
        self::assertSame(
            42,
            $this->makeFunctions(
                data: [
                    'count' => 42,
                ],
            )->session('count'),
        );
    }

    public function testSessionReturnsArrayValueVerbatim(): void
    {
        $payload = [
            'role' => 'admin',
            'level' => 5,
        ];

        self::assertSame(
            $payload,
            $this->makeFunctions(
                data: [
                    'profile' => $payload,
                ],
            )->session('profile'),
        );
    }

    public function testSessionIdReturnsSessionIdentifier(): void
    {
        self::assertSame(
            'sess-abc-123',
            $this->makeFunctions(
                identifier: 'sess-abc-123',
            )->sessionId(),
        );
    }

    public function testHasSessionReturnsTrueForExistingKey(): void
    {
        self::assertTrue(
            $this->makeFunctions(
                data: [
                    'user' => 'alice',
                ],
            )->hasSession('user'),
        );
    }

    public function testHasSessionReturnsFalseForMissingKey(): void
    {
        self::assertFalse($this->makeFunctions()->hasSession('missing'));
    }

    public function testHasSessionReturnsTrueForKeyWithNullValue(): void
    {
        self::assertTrue(
            $this->makeFunctions(
                data: [
                    'nullable' => null,
                ],
            )->hasSession('nullable'),
        );
    }
}
