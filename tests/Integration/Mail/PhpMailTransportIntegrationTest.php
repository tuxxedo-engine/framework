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
        $this->api->deleteAll();

        $this->transport = new PhpMailTransport(
            mailer: new SmtpForwardingPhpMailer(
                host: MailpitTestEnv::host(),
                port: MailpitTestEnv::smtpPort(),
            ),
        );
    }

    public function testSendDeliversMessageToMailpit(): void
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
                subject: 'Integration ping',
                body: 'Hello from PhpMailTransportIntegrationTest',
            ),
        );

        $messages = $this->api->list();

        self::assertCount(1, $messages);
        self::assertSame(
            'Integration ping',
            $messages[0]['Subject'] ?? null,
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
                subject: 'Body check',
                body: 'Line one.',
            ),
        );

        $messages = $this->api->list();

        self::assertCount(1, $messages);

        $id = $messages[0]['ID'] ?? null;

        self::assertNotNull($id);
        self::assertIsString($id);

        $raw = $this->api->fetchRaw(
            id: $id,
        );

        self::assertStringContainsString(
            'Line one.',
            $raw,
        );
    }
}
