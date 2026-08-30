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

namespace Integration\Mail;

use PHPUnit\Framework\TestCase;
use Support\Mail\MailpitApiClient;
use Support\Mail\MailpitAuthServerProbe;
use Support\Mail\MailpitAuthTestEnv;
use Support\Mail\MailpitServerProbe;
use Support\Mail\MailpitTestEnv;
use Support\Mail\RealMailpitAuthIntegrationSetup;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Transport\SmtpMail\Config\SmtpTransportConfig;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpAuth;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSocket;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransport;

class SmtpTransportAuthIntegrationTest extends TestCase
{
    use RealMailpitAuthIntegrationSetup;

    private MailpitApiClient $api;
    private string $marker;

    protected function mailpitAuthSkipReason(): ?string
    {
        return MailpitAuthServerProbe::unavailableReason();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new MailpitApiClient(
            baseUrl: MailpitAuthTestEnv::apiUrl(),
        );
        $this->marker = 'smtp-auth-' . \bin2hex(\random_bytes(6));
    }

    public function testPlainAuthDeliversMessage(): void
    {
        $manager = new MailManager(
            transport: $this->transportWithAuth(
                auth: SmtpAuth::PLAIN,
            ),
        );

        $subject = $this->markedSubject('plain');

        $manager->send(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'recipient@example.com',
                subject: $subject,
                body: 'body',
            ),
        );

        self::assertContains(
            $subject,
            $this->subjectsMatchingMarker(),
        );
    }

    public function testLoginAuthDeliversMessage(): void
    {
        $manager = new MailManager(
            transport: $this->transportWithAuth(
                auth: SmtpAuth::LOGIN,
            ),
        );

        $subject = $this->markedSubject('login');

        $manager->send(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'recipient@example.com',
                subject: $subject,
                body: 'body',
            ),
        );

        self::assertContains(
            $subject,
            $this->subjectsMatchingMarker(),
        );
    }

    public function testAuthAgainstNonAuthServerFails(): void
    {
        if (MailpitServerProbe::unavailableReason() !== null) {
            self::markTestSkipped('Base mailpit instance required for this test');
        }

        $transport = new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitTestEnv::host(),
                port: MailpitTestEnv::smtpPort(),
                tls: SmtpTls::NONE,
                auth: SmtpAuth::PLAIN,
                username: 'user',
                password: 'pass',
            ),
            socket: new SmtpSocket(),
        );

        $manager = new MailManager(
            transport: $transport,
        );

        $this->expectException(MailException::class);

        $manager->send(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'recipient@example.com',
                subject: $this->markedSubject('noauth-fail'),
                body: 'body',
            ),
        );
    }

    private function transportWithAuth(
        SmtpAuth $auth,
    ): SmtpTransport {
        return new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitAuthTestEnv::host(),
                port: MailpitAuthTestEnv::smtpPort(),
                tls: SmtpTls::NONE,
                auth: $auth,
                username: 'testuser',
                password: 'testpass',
            ),
            socket: new SmtpSocket(),
        );
    }

    private function markedSubject(
        string $label,
    ): string {
        return $this->marker . ' ' . $label;
    }

    /**
     * @return list<string>
     */
    private function subjectsMatchingMarker(): array
    {
        $subjects = [];

        foreach ($this->api->list() as $envelope) {
            $subject = $envelope['Subject'] ?? null;

            if (\is_string($subject) && \str_starts_with($subject, $this->marker)) {
                $subjects[] = $subject;
            }
        }

        return $subjects;
    }
}
