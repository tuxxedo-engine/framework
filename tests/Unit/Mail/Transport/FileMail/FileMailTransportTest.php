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

namespace Unit\Mail\Transport\FileMail;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Transport\FileMail\Config\FileMailTransportConfig;
use Tuxxedo\Mail\Transport\FileMail\FileMailTransport;

class FileMailTransportTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'tuxxedo-filemail-' . \bin2hex(\random_bytes(8));
        \mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $entries = \glob($this->directory . \DIRECTORY_SEPARATOR . '*');

        if ($entries === false) {
            $entries = [];
        }

        foreach ($entries as $entry) {
            \unlink($entry);
        }

        \rmdir($this->directory);
    }

    /**
     * @param non-empty-string|null $messageId
     */
    private function makeMessage(
        ?string $messageId = null,
    ): Message {
        return new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'to1@example.com',
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
            messageId: $messageId,
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

    private function transportWithDirectory(
        string $directory,
    ): FileMailTransport {
        return new FileMailTransport(
            config: new FileMailTransportConfig(
                directory: $directory,
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function directoryFilenames(): array
    {
        $entries = \glob($this->directory . \DIRECTORY_SEPARATOR . '*');

        if ($entries === false) {
            $entries = [];
        }

        return \array_map(
            static fn (string $path): string => \basename($path),
            $entries,
        );
    }

    public function testSendWritesOneEmlFilePerMessageWithWireContents(): void
    {
        $transport = $this->transportWithDirectory(
            directory: $this->directory,
        );

        $serialized = $this->serialize($this->makeMessage());

        $transport->send($serialized);

        $filenames = $this->directoryFilenames();

        self::assertCount(1, $filenames);
        self::assertSame(
            $serialized->wire,
            \file_get_contents($this->directory . \DIRECTORY_SEPARATOR . $filenames[0]),
        );
    }

    public function testSendWritesOneFilePerMessageWhenGivenMultiple(): void
    {
        $transport = $this->transportWithDirectory(
            directory: $this->directory,
        );

        $transport->send(
            $this->serialize($this->makeMessage()),
            $this->serialize($this->makeMessage()),
            $this->serialize($this->makeMessage()),
        );

        self::assertCount(3, $this->directoryFilenames());
    }

    public function testFilenameSanitizesSpecialCharsFromMessageId(): void
    {
        $transport = $this->transportWithDirectory(
            directory: $this->directory,
        );

        $transport->send(
            $this->serialize(
                $this->makeMessage(
                    messageId: '<abc123@example.com>',
                ),
            ),
        );

        $filenames = $this->directoryFilenames();

        self::assertCount(1, $filenames);
        self::assertSame('abc123-example.com.eml', $filenames[0]);
    }

    public function testFilenameFallsBackToRandomHexWhenSanitizedSlugIsEmpty(): void
    {
        $transport = $this->transportWithDirectory(
            directory: $this->directory,
        );

        $transport->send(
            $this->serialize(
                $this->makeMessage(
                    messageId: '@@@@',
                ),
            ),
        );

        $filenames = $this->directoryFilenames();

        self::assertCount(1, $filenames);
        self::assertMatchesRegularExpression('/^[a-f0-9]{16}\.eml$/', $filenames[0]);
    }

    public function testDirectoryWithTrailingSeparatorDoesNotDoubleSeparate(): void
    {
        $transport = $this->transportWithDirectory(
            directory: $this->directory . \DIRECTORY_SEPARATOR,
        );

        $transport->send(
            $this->serialize($this->makeMessage()),
        );

        self::assertCount(1, $this->directoryFilenames());
    }

    public function testSendWithResultReturnsAcceptedForAllRecipientsOnSuccess(): void
    {
        $transport = $this->transportWithDirectory(
            directory: $this->directory,
        );

        $results = $transport->sendWithResult(
            $this->serialize($this->makeMessage()),
        );

        self::assertCount(1, $results);
        self::assertCount(3, $results[0]->outcomes);

        foreach ($results[0]->outcomes as $outcome) {
            self::assertSame(RecipientStatus::ACCEPTED, $outcome->status);
            self::assertNull($outcome->summary);
        }
    }

    public function testCreateTransportResolvesFileMailTransportFromContainer(): void
    {
        $config = new FileMailTransportConfig(
            directory: $this->directory,
        );

        $container = new Container();
        $container->singleton($config);

        $transport = $config->createTransport(
            container: $container,
        );

        self::assertInstanceOf(FileMailTransport::class, $transport);
    }
}
