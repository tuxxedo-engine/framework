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
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Transport\SmtpMail\Config\SmtpTransportConfig;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpAuth;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSocket;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransport;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTransportMode;

class SmtpTransportIntegrationTest extends TestCase
{
    use RealMailpitIntegrationSetup;

    private MailpitApiClient $api;
    private SmtpTransport $transport;
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
        $this->marker = 'smtp-' . \bin2hex(\random_bytes(6));

        $this->transport = new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitTestEnv::host(),
                port: MailpitTestEnv::smtpPort(),
                tls: SmtpTls::NONE,
                auth: SmtpAuth::NONE,
            ),
            socket: new SmtpSocket(),
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
                body: 'Hello from SmtpTransportIntegrationTest',
            ),
        );

        $matches = $this->messagesMatchingMarker();

        self::assertCount(1, $matches);
        self::assertSame(
            $subject,
            $matches[0]['Subject'] ?? null,
        );
    }

    public function testSendPreservesFromAndToAddresses(): void
    {
        $manager = new MailManager(
            transport: $this->transport,
        );

        $manager->send(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: $this->markedSubject('addresses'),
                body: 'body',
            ),
        );

        $matches = $this->messagesMatchingMarker();

        self::assertCount(1, $matches);

        /** @var array{Address?: string} $from */
        $from = $matches[0]['From'] ?? [];

        /** @var list<array{Address?: string}> $toList */
        $toList = $matches[0]['To'] ?? [];

        self::assertSame(
            'from@example.com',
            $from['Address'] ?? null,
        );
        self::assertSame(
            'to@example.com',
            $toList[0]['Address'] ?? null,
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

    public function testSendDeliversMultipartHtmlWithAlternativeText(): void
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
                subject: $this->markedSubject('multipart'),
                body: '<p>HTML body</p>',
                bodyType: BodyType::HTML,
                alternativeText: 'Plain-text alternative',
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
            '<p>HTML body</p>',
            $raw,
        );
        self::assertStringContainsString(
            'Plain-text alternative',
            $raw,
        );
    }

    public function testSendDeliversMultipleMessagesInOneCall(): void
    {
        $manager = new MailManager(
            transport: $this->transport,
        );

        $manager->send(
            ...$this->makeNumberedMessages(
                count: 3,
                label: 'batch',
            ),
        );

        self::assertSame(
            [
                $this->markedSubject('batch-1'),
                $this->markedSubject('batch-2'),
                $this->markedSubject('batch-3'),
            ],
            $this->sortedSubjectsMatchingMarker(),
        );
    }

    public function testSendInPerMessageModeDeliversAllMessages(): void
    {
        $manager = new MailManager(
            transport: $this->transportWithMode(
                mode: SmtpTransportMode::PER_MESSAGE,
            ),
        );

        $manager->send(
            ...$this->makeNumberedMessages(
                count: 3,
                label: 'permsg',
            ),
        );

        self::assertSame(
            [
                $this->markedSubject('permsg-1'),
                $this->markedSubject('permsg-2'),
                $this->markedSubject('permsg-3'),
            ],
            $this->sortedSubjectsMatchingMarker(),
        );
    }

    public function testSendInReuseConnectionModeDeliversAllMessages(): void
    {
        $manager = new MailManager(
            transport: $this->transportWithMode(
                mode: SmtpTransportMode::REUSE_CONNECTION,
            ),
        );

        $manager->send(
            ...$this->makeNumberedMessages(
                count: 3,
                label: 'reuse',
            ),
        );

        self::assertSame(
            [
                $this->markedSubject('reuse-1'),
                $this->markedSubject('reuse-2'),
                $this->markedSubject('reuse-3'),
            ],
            $this->sortedSubjectsMatchingMarker(),
        );
    }

    public function testSendInReuseUpToNModeDeliversAllMessagesAcrossReconnect(): void
    {
        $manager = new MailManager(
            transport: $this->transportWithMode(
                mode: SmtpTransportMode::REUSE_UP_TO_N,
                reuseLimit: 2,
            ),
        );

        $manager->send(
            ...$this->makeNumberedMessages(
                count: 5,
                label: 'reuseN',
            ),
        );

        self::assertSame(
            [
                $this->markedSubject('reuseN-1'),
                $this->markedSubject('reuseN-2'),
                $this->markedSubject('reuseN-3'),
                $this->markedSubject('reuseN-4'),
                $this->markedSubject('reuseN-5'),
            ],
            $this->sortedSubjectsMatchingMarker(),
        );
    }

    public function testConfigCreateTransportResolvesSmtpTransportFromContainer(): void
    {
        $container = new Container();
        $container->singleton(
            class: new SmtpTransportConfig(
                host: MailpitTestEnv::host(),
                port: MailpitTestEnv::smtpPort(),
                tls: SmtpTls::NONE,
                auth: SmtpAuth::NONE,
            ),
        );
        $container->singleton(
            class: new SmtpSocket(),
        );

        $transport = (new SmtpTransportConfig(
            host: MailpitTestEnv::host(),
            port: MailpitTestEnv::smtpPort(),
        ))->createTransport(
            container: $container,
        );

        self::assertInstanceOf(
            SmtpTransport::class,
            $transport,
        );
    }

    public function testSendWithResultReturnsAcceptedOutcomesForEachRecipient(): void
    {
        $manager = new MailManager(
            transport: $this->transport,
        );

        $results = $manager->sendWithResult(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: [
                    'a@example.com',
                    'b@example.com',
                ],
                subject: $this->markedSubject('withresult'),
                body: 'body',
                cc: 'c@example.com',
            ),
        );

        self::assertCount(1, $results);
        self::assertCount(3, $results[0]->outcomes);

        foreach ($results[0]->outcomes as $outcome) {
            self::assertSame(
                RecipientStatus::ACCEPTED,
                $outcome->status,
            );
        }
    }

    public function testSendWithResultWithMultipleMessagesReturnsResultPerMessage(): void
    {
        $manager = new MailManager(
            transport: $this->transport,
        );

        $results = $manager->sendWithResult(
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'first@example.com',
                subject: $this->markedSubject('withresult-a'),
                body: 'body',
            ),
            new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: 'second@example.com',
                subject: $this->markedSubject('withresult-b'),
                body: 'body',
            ),
        );

        self::assertCount(2, $results);
    }

    private function markedSubject(
        string $label,
    ): string {
        return $this->marker . ' ' . $label;
    }

    private function transportWithMode(
        SmtpTransportMode $mode,
        int $reuseLimit = 0,
    ): SmtpTransport {
        return new SmtpTransport(
            config: new SmtpTransportConfig(
                host: MailpitTestEnv::host(),
                port: MailpitTestEnv::smtpPort(),
                tls: SmtpTls::NONE,
                auth: SmtpAuth::NONE,
                mode: $mode,
                reuseLimit: $reuseLimit,
            ),
            socket: new SmtpSocket(),
        );
    }

    /**
     * @return list<Message>
     */
    private function makeNumberedMessages(
        int $count,
        string $label,
    ): array {
        $messages = [];

        for ($index = 1; $index <= $count; $index++) {
            $messages[] = new Message(
                from: new Address(
                    email: 'demo@example.com',
                ),
                to: \sprintf(
                    'recipient-%d@example.com',
                    $index,
                ),
                subject: $this->markedSubject(
                    label: \sprintf(
                        '%s-%d',
                        $label,
                        $index,
                    ),
                ),
                body: 'body',
            );
        }

        return $messages;
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

    /**
     * @return list<string>
     */
    private function sortedSubjectsMatchingMarker(): array
    {
        $subjects = [];

        foreach ($this->messagesMatchingMarker() as $envelope) {
            $subject = $envelope['Subject'] ?? null;

            if (\is_string($subject)) {
                $subjects[] = $subject;
            }
        }

        \sort($subjects);

        return $subjects;
    }
}
