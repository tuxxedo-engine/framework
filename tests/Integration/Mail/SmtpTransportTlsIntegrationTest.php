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
use Support\Mail\MailpitTlsServerProbe;
use Support\Mail\MailpitTlsTestEnv;
use Support\Mail\RealMailpitTlsIntegrationSetup;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Transport\SmtpMail\Config\SmtpTransportConfig;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpAuth;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSocket;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransport;

class SmtpTransportTlsIntegrationTest extends TestCase
{
    use RealMailpitTlsIntegrationSetup;

    private MailpitApiClient $api;
    private string $marker;

    protected function mailpitTlsSkipReason(): ?string
    {
        return MailpitTlsServerProbe::unavailableReason();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new MailpitApiClient(
            baseUrl: MailpitTlsTestEnv::apiUrl(),
        );
        $this->marker = 'smtp-tls-' . \bin2hex(\random_bytes(6));
    }

    public function testStarttlsWithVerifyPeerDisabledDelivers(): void
    {
        $transport = new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitTlsTestEnv::host(),
                port: MailpitTlsTestEnv::smtpPort(),
                tls: SmtpTls::STARTTLS,
                auth: SmtpAuth::NONE,
                verifyPeer: false,
            ),
            socket: new SmtpSocket(),
        );

        $manager = new MailManager(
            transport: $transport,
        );

        $subject = $this->markedSubject('starttls-no-verify');

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

    public function testStarttlsWithCaFilePinnedToTestCertDelivers(): void
    {
        $transport = new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitTlsTestEnv::host(),
                port: MailpitTlsTestEnv::smtpPort(),
                tls: SmtpTls::STARTTLS,
                auth: SmtpAuth::NONE,
                verifyPeer: true,
                caFile: MailpitTlsTestEnv::caFile(),
            ),
            socket: new SmtpSocket(),
        );

        $manager = new MailManager(
            transport: $transport,
        );

        $subject = $this->markedSubject('starttls-pinned');

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

    public function testStarttlsWithVerifyPeerRejectsSelfSignedWithoutCaFile(): void
    {
        $transport = new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitTlsTestEnv::host(),
                port: MailpitTlsTestEnv::smtpPort(),
                tls: SmtpTls::STARTTLS,
                auth: SmtpAuth::NONE,
                verifyPeer: true,
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
                subject: $this->markedSubject('starttls-unpinned-fail'),
                body: 'body',
            ),
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
