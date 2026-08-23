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

namespace Unit\Mail\Transport\SendMail;

use PHPUnit\Framework\TestCase;
use Support\Process\RecordingProcessRunner;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Transport\SendMail\Config\SendMailTransportConfig;
use Tuxxedo\Mail\Transport\SendMail\SendMailTransport;
use Tuxxedo\Process\ProcessException;
use Tuxxedo\Process\ProcessResult;

class SendMailTransportTest extends TestCase
{
    private function makeMessage(
        ?Address $returnPath = null,
    ): Message {
        return new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'to1@example.com',
                ),
                new Address(
                    email: 'to2@example.com',
                ),
            ],
            subject: 'Test',
            body: 'body',
            cc: [
                new Address(
                    email: 'cc@example.com',
                ),
            ],
            bcc: [
                new Address(
                    email: 'bcc@example.com',
                ),
            ],
            returnPath: $returnPath,
        );
    }

    private function serialize(
        Message $message,
    ): SerializedMessage {
        return new SerializedMessage(
            source: $message,
            headers: 'Subject: Test',
            body: 'body',
        );
    }

    public function testSendBuildsProcessCommandWithBinaryAndDefaultArgs(): void
    {
        $runner = new RecordingProcessRunner();
        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $transport->send($this->serialize($this->makeMessage()));

        self::assertCount(1, $runner->commands);

        $command = $runner->commands[0];

        self::assertSame('/usr/sbin/sendmail', $command->binary);
        self::assertSame(
            [
                '-t',
                '-i',
                '-f',
                'sender@example.com',
            ],
            $command->arguments,
        );
    }

    public function testSendPipesWireBytesAsStdin(): void
    {
        $runner = new RecordingProcessRunner();
        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $serialized = $this->serialize($this->makeMessage());

        $transport->send($serialized);

        self::assertSame($serialized->wire, $runner->commands[0]->stdin);
    }

    public function testSendUsesReturnPathAsEnvelopeFromWhenSet(): void
    {
        $runner = new RecordingProcessRunner();
        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $transport->send(
            $this->serialize(
                $this->makeMessage(
                    returnPath: new Address(
                        email: 'bounces@example.com',
                    ),
                ),
            ),
        );

        self::assertContains('bounces@example.com', $runner->commands[0]->arguments);
        self::assertNotContains('sender@example.com', $runner->commands[0]->arguments);
    }

    public function testSendPassesConfiguredArgumentsIncludingOverrides(): void
    {
        $runner = new RecordingProcessRunner();
        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(
                binary: '/opt/msmtp',
                arguments: [
                    '-t',
                    '--custom-flag',
                ],
                timeoutSeconds: 60,
            ),
            processRunner: $runner,
        );

        $transport->send($this->serialize($this->makeMessage()));

        $command = $runner->commands[0];

        self::assertSame('/opt/msmtp', $command->binary);
        self::assertSame(
            [
                '-t',
                '--custom-flag',
                '-f',
                'sender@example.com',
            ],
            $command->arguments,
        );
        self::assertSame(60, $command->timeoutSeconds);
    }

    public function testSendThrowsMailExceptionOnNonZeroExitCode(): void
    {
        $runner = new RecordingProcessRunner();
        $runner->setResult(
            new ProcessResult(
                exitCode: 78,
                stdout: '',
                stderr: 'sendmail: unable to deliver',
            ),
        );

        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        try {
            $transport->send($this->serialize($this->makeMessage()));

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString('78', $exception->getMessage());
            self::assertStringContainsString('unable to deliver', $exception->getMessage());
        }
    }

    public function testSendThrowsMailExceptionWhenRunnerThrows(): void
    {
        $runner = new RecordingProcessRunner();
        $runner->setException(
            ProcessException::fromLaunchFailure('/usr/sbin/sendmail'),
        );

        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $this->expectException(MailException::class);

        $transport->send($this->serialize($this->makeMessage()));
    }

    public function testSendWithResultReturnsAcceptedForAllRecipientsOnSuccess(): void
    {
        $runner = new RecordingProcessRunner();
        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $results = $transport->sendWithResult($this->serialize($this->makeMessage()));

        self::assertCount(1, $results);
        self::assertCount(4, $results[0]->outcomes);

        foreach ($results[0]->outcomes as $outcome) {
            self::assertSame(RecipientStatus::ACCEPTED, $outcome->status);
            self::assertNull($outcome->summary);
        }
    }

    public function testSendWithResultReturnsPermanentFailureForAllRecipientsOnError(): void
    {
        $runner = new RecordingProcessRunner();
        $runner->setResult(
            new ProcessResult(
                exitCode: 1,
                stdout: '',
                stderr: 'boom',
            ),
        );

        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $results = $transport->sendWithResult($this->serialize($this->makeMessage()));

        self::assertCount(1, $results);

        foreach ($results[0]->outcomes as $outcome) {
            self::assertSame(RecipientStatus::PERMANENT_FAILURE, $outcome->status);
            self::assertNotNull($outcome->summary);
        }
    }

    public function testSendMultipleMessagesInvokesRunnerPerMessage(): void
    {
        $runner = new RecordingProcessRunner();
        $transport = new SendMailTransport(
            config: new SendMailTransportConfig(),
            processRunner: $runner,
        );

        $transport->send(
            $this->serialize($this->makeMessage()),
            $this->serialize($this->makeMessage()),
            $this->serialize($this->makeMessage()),
        );

        self::assertCount(3, $runner->commands);
    }

    public function testCreateTransportResolvesSendMailTransportFromContainer(): void
    {
        $container = new Container();
        $container->singleton(new RecordingProcessRunner());

        $config = new SendMailTransportConfig();
        $transport = $config->createTransport(
            container: $container,
        );

        self::assertInstanceOf(SendMailTransport::class, $transport);
    }
}
