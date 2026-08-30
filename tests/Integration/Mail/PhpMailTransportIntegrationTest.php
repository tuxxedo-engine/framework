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
use Support\Mail\MailpitServerProbe;
use Support\Mail\MailpitTestEnv;
use Support\Mail\RealMailpitIntegrationSetup;
use Support\Mail\SmtpForwardingPhpMailer;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Transport\PhpMail\PhpMailTransport;

class PhpMailTransportIntegrationTest extends TestCase
{
    use RealMailpitIntegrationSetup;

    private MailpitApiClient $api;
    private PhpMailTransport $transport;
    private string $marker;

    protected function mailpitSkipReason(): ?string
    {
        return MailpitServerProbe::unavailableReason();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new MailpitApiClient(
            baseUrl: MailpitTestEnv::apiUrl(),
        );
        $this->marker = 'php-' . \bin2hex(\random_bytes(6));

        $this->transport = new PhpMailTransport(
            mailer: new SmtpForwardingPhpMailer(
                host: MailpitTestEnv::host(),
                port: MailpitTestEnv::smtpPort(),
            ),
        );
    }

    public function testSendDeliversMessageToMailpit(): void
    {
        $subject = $this->markedSubject('ping');
        $manager = new MailManager(
            transport: $this->transport,
        );

        $manager->send(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'recipient@example.com',
                subject: $subject,
                body: 'Hello from PhpMailTransportIntegrationTest',
            ),
        );

        $matches = $this->messagesMatchingMarker();

        self::assertCount(1, $matches);
        self::assertSame(
            $subject,
            $matches[0]['Subject'] ?? null,
        );
    }

    public function testSendDeliversBodyIntact(): void
    {
        $manager = new MailManager(
            transport: $this->transport,
        );

        $manager->send(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'recipient@example.com',
                subject: $this->markedSubject('body'),
                body: 'Line one.',
            ),
        );

        $matches = $this->messagesMatchingMarker();

        self::assertCount(1, $matches);

        $id = $matches[0]['ID'] ?? null;

        self::assertIsString($id);

        $raw = $this->api->fetchRaw(
            id: $id,
        );

        self::assertStringContainsString(
            'Line one.',
            $raw,
        );
    }

    private function markedSubject(
        string $label,
    ): string {
        return $this->marker . ' ' . $label;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagesMatchingMarker(): array
    {
        $matches = [];

        foreach ($this->api->list() as $envelope) {
            $subject = $envelope['Subject'] ?? null;

            if (\is_string($subject) && \str_starts_with($subject, $this->marker)) {
                $matches[] = $envelope;
            }
        }

        return $matches;
    }
}
