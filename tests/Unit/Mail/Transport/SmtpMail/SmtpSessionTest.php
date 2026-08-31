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

namespace Unit\Mail\Transport\SmtpMail;

use PHPUnit\Framework\TestCase;
use Support\Mail\Transport\SmtpMail\ScriptedSmtpSocket;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpAuth;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpResponse;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSession;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;
use Tuxxedo\Mail\Transport\SmtpMail\Xoauth\StaticXoauthTokenProvider;

class SmtpSessionTest extends TestCase
{
    private const string EHLO_DOMAIN = 'test.local';

    private function response(
        int $code,
        string $line,
    ): SmtpResponse {
        return new SmtpResponse(
            code: $code,
            lines: [
                $line,
            ],
        );
    }

    /**
     * @param list<string> $lines
     */
    private function multiLineResponse(
        int $code,
        array $lines,
    ): SmtpResponse {
        return new SmtpResponse(
            code: $code,
            lines: $lines,
        );
    }

    /**
     * @param list<string> $ehloExtensions
     * @param list<SmtpResponse> $additionalResponses
     *
     * @return array{0: SmtpSession, 1: ScriptedSmtpSocket}
     */
    private function openSession(
        array $ehloExtensions = [
            'mail.test hello',
        ],
        array $additionalResponses = [],
        SmtpTls $tls = SmtpTls::NONE,
    ): array {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(220, 'mail.test ESMTP ready'),
                $this->multiLineResponse(250, $ehloExtensions),
                ...$additionalResponses,
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: $tls,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );

        return [
            $session,
            $socket,
        ];
    }

    private function serialized(
        string $body = 'body',
    ): SerializedMessageInterface {
        return new SerializedMessage(
            source: new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'test',
                body: 'body',
            ),
            headers: 'Subject: test',
            body: $body,
        );
    }

    private function envelopeFrom(): Address
    {
        return new Address(
            email: 'from@example.com',
        );
    }

    private function recipient(
        string $email = 'to@example.com',
    ): Address {
        return new Address(
            email: $email,
        );
    }

    public function testOpenSendsGreetingAndEhloAndParsesCapabilities(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'PIPELINING',
                'SIZE 10485760',
                'AUTH PLAIN LOGIN CRAM-MD5 XOAUTH2',
            ],
        );

        self::assertTrue($session->capabilities->supports('PIPELINING'));
        self::assertSame(
            'EHLO ' . self::EHLO_DOMAIN,
            $socket->writtenCommands[0],
        );
    }

    public function testOpenThrowsWhenGreetingCodeUnexpected(): void
    {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(554, 'Transaction failed'),
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $this->expectException(MailException::class);

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: SmtpTls::NONE,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );
    }

    public function testHandshakeFallsBackToHeloWhenEhloRejected(): void
    {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(220, 'mail.test ESMTP ready'),
                $this->response(500, 'Command not recognized'),
                $this->response(250, 'mail.test hello'),
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: SmtpTls::NONE,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );

        self::assertSame(
            [
                'EHLO ' . self::EHLO_DOMAIN,
                'HELO ' . self::EHLO_DOMAIN,
            ],
            $socket->writtenCommands,
        );
    }

    public function testHandshakeThrowsWhenBothEhloAndHeloRejected(): void
    {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(220, 'mail.test ESMTP ready'),
                $this->response(500, 'no EHLO'),
                $this->response(502, 'no HELO'),
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $this->expectException(MailException::class);

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: SmtpTls::NONE,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );
    }

    public function testOpenNegotiatesStarttlsAndReissuesEhlo(): void
    {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(220, 'mail.test ESMTP ready'),
                $this->multiLineResponse(250, [
                    'mail.test hello',
                    'STARTTLS',
                ]),
                $this->response(220, 'Ready to start TLS'),
                $this->response(250, 'mail.test hello'),
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: SmtpTls::STARTTLS,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );

        self::assertTrue($socket->cryptoEnabled);
        self::assertContains(
            'STARTTLS',
            $socket->writtenCommands,
        );
    }

    public function testStarttlsThrowsWhenServerDoesNotAdvertiseIt(): void
    {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(220, 'mail.test ESMTP ready'),
                $this->response(250, 'mail.test hello'),
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $this->expectException(MailException::class);

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: SmtpTls::STARTTLS,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );
    }

    public function testStarttlsThrowsWhenServerRefusesReadyResponse(): void
    {
        $socket = new ScriptedSmtpSocket();

        $socket->queue(
            responses: [
                $this->response(220, 'mail.test ESMTP ready'),
                $this->multiLineResponse(250, [
                    'mail.test hello',
                    'STARTTLS',
                ]),
                $this->response(454, 'TLS not available'),
            ],
        );

        $session = new SmtpSession(
            socket: $socket,
        );

        $this->expectException(MailException::class);

        $session->open(
            host: '127.0.0.1',
            port: 25,
            tls: SmtpTls::STARTTLS,
            connectTimeout: 5,
            readTimeout: 5,
            verifyPeer: false,
            caFile: null,
            ehloDomain: self::EHLO_DOMAIN,
        );
    }

    public function testSendMessageSequentialSendsMailFromRcptDataAndBody(): void
    {
        [$session, $socket] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'OK'),
                $this->response(250, 'OK'),
                $this->response(354, 'Start mail input'),
                $this->response(250, 'Message accepted'),
            ],
        );

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        self::assertContains(
            'MAIL FROM:<from@example.com>',
            $socket->writtenCommands,
        );

        self::assertContains(
            'RCPT TO:<to@example.com>',
            $socket->writtenCommands,
        );

        self::assertContains(
            'DATA',
            $socket->writtenCommands,
        );
    }

    public function testSendMessageSequentialThrowsWhenRcptRejected(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'OK'),
                $this->response(550, 'User unknown'),
            ],
        );

        $this->expectException(MailException::class);

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );
    }

    public function testSendMessageSequentialThrowsWhenDataPromptRejected(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'OK'),
                $this->response(250, 'OK'),
                $this->response(554, 'Rejected'),
            ],
        );

        $this->expectException(MailException::class);

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );
    }

    public function testSendMessageSequentialThrowsWhenBodyRejected(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'OK'),
                $this->response(250, 'OK'),
                $this->response(354, 'Start mail input'),
                $this->response(552, 'Message too large'),
            ],
        );

        $this->expectException(MailException::class);

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );
    }

    public function testSendMessagePipelinedSendsAllCommandsAsOneChunk(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'PIPELINING',
            ],
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(250, 'RCPT TO OK'),
                $this->response(354, 'Start mail input'),
                $this->response(250, 'Message accepted'),
            ],
        );

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        self::assertNotEmpty($socket->writtenRaw);
        self::assertStringContainsString(
            'MAIL FROM:<from@example.com>',
            $socket->writtenRaw[0],
        );

        self::assertStringContainsString(
            'RCPT TO:<to@example.com>',
            $socket->writtenRaw[0],
        );
    }

    public function testSendMessagePipelinedThrowsFirstFailure(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'PIPELINING',
            ],
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(550, 'RCPT TO rejected'),
            ],
        );

        $this->expectException(MailException::class);

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );
    }

    public function testSendMessageWithResultReturnsFailureOutcomesWhenMailFromRejected(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(550, 'Sender rejected'),
            ],
        );

        $outcomes = $session->sendMessageWithResult(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient('a@example.com'),
                $this->recipient('b@example.com'),
            ],
        );

        self::assertCount(2, $outcomes);

        foreach ($outcomes as $outcome) {
            self::assertSame(550, $outcome->code);
        }
    }

    public function testSendMessageWithResultMarksIndividualRecipientsPerRcptResponse(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(250, 'RCPT TO OK'),
                $this->response(550, 'User unknown'),
                $this->response(354, 'Start mail input'),
                $this->response(250, 'Message accepted'),
            ],
        );

        $outcomes = $session->sendMessageWithResult(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient('a@example.com'),
                $this->recipient('b@example.com'),
            ],
        );

        self::assertCount(2, $outcomes);
        self::assertSame(250, $outcomes[0]->code);
        self::assertSame(550, $outcomes[1]->code);
    }

    public function testSendMessageWithResultReturnsEarlyWhenAllRecipientsRejected(): void
    {
        [$session, $socket] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(550, 'a rejected'),
                $this->response(550, 'b rejected'),
            ],
        );

        $outcomes = $session->sendMessageWithResult(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient('a@example.com'),
                $this->recipient('b@example.com'),
            ],
        );

        self::assertCount(2, $outcomes);
        self::assertNotContains(
            'DATA',
            $socket->writtenCommands,
        );
    }

    public function testSendMessageWithResultOverridesAcceptedOutcomesWhenDataPromptRejected(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(250, 'RCPT TO OK'),
                $this->response(554, 'DATA rejected'),
            ],
        );

        $outcomes = $session->sendMessageWithResult(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        self::assertSame(554, $outcomes[0]->code);
    }

    public function testSendMessageWithResultOverridesAcceptedOutcomesWhenBodyRejected(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(250, 'RCPT TO OK'),
                $this->response(354, 'Start mail input'),
                $this->response(552, 'Body rejected'),
            ],
        );

        $outcomes = $session->sendMessageWithResult(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        self::assertSame(552, $outcomes[0]->code);
    }

    public function testAuthenticatePlainSendsAuthPlainAndSucceeds(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH PLAIN LOGIN CRAM-MD5 XOAUTH2',
            ],
            additionalResponses: [
                $this->response(235, 'Authentication successful'),
            ],
        );

        $session->authenticate(
            mechanism: SmtpAuth::PLAIN,
            username: 'user',
            password: 'pass',
            xoauthTokenProvider: null,
        );

        self::assertStringStartsWith(
            'AUTH PLAIN ',
            $socket->writtenCommands[1],
        );
    }

    public function testAuthenticateNoneIsANoop(): void
    {
        [$session, $socket] = $this->openSession();
        $writtenBefore = \sizeof($socket->writtenCommands);

        $session->authenticate(
            mechanism: SmtpAuth::NONE,
            username: '',
            password: '',
            xoauthTokenProvider: null,
        );

        self::assertSame($writtenBefore, \sizeof($socket->writtenCommands));
    }

    public function testAuthenticateThrowsWhenMechanismNotAdvertised(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH PLAIN',
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::LOGIN,
            username: 'user',
            password: 'pass',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticatePlainThrowsWhenServerRejects(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH PLAIN',
            ],
            additionalResponses: [
                $this->response(535, 'Auth rejected'),
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::PLAIN,
            username: 'user',
            password: 'wrong',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticateLoginWalksThroughChallengeChain(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH LOGIN',
            ],
            additionalResponses: [
                $this->response(334, \base64_encode('Username:')),
                $this->response(334, \base64_encode('Password:')),
                $this->response(235, 'Authentication successful'),
            ],
        );

        $session->authenticate(
            mechanism: SmtpAuth::LOGIN,
            username: 'user',
            password: 'pass',
            xoauthTokenProvider: null,
        );

        self::assertContains(
            'AUTH LOGIN',
            $socket->writtenCommands,
        );

        self::assertContains(
            \base64_encode('user'),
            $socket->writtenCommands,
        );

        self::assertContains(
            \base64_encode('pass'),
            $socket->writtenCommands,
        );
    }

    public function testAuthenticateLoginThrowsWhenServerRejectsChallenge(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH LOGIN',
            ],
            additionalResponses: [
                $this->response(535, 'No auth'),
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::LOGIN,
            username: 'user',
            password: 'pass',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticateLoginThrowsWhenFinalStepRejected(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH LOGIN',
            ],
            additionalResponses: [
                $this->response(334, \base64_encode('Username:')),
                $this->response(334, \base64_encode('Password:')),
                $this->response(535, 'Bad credentials'),
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::LOGIN,
            username: 'user',
            password: 'wrong',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticateCramMd5SendsHmacDigest(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH CRAM-MD5',
            ],
            additionalResponses: [
                $this->response(334, \base64_encode('<challenge@server>')),
                $this->response(235, 'Authentication successful'),
            ],
        );

        $session->authenticate(
            mechanism: SmtpAuth::CRAM_MD5,
            username: 'user',
            password: 'pass',
            xoauthTokenProvider: null,
        );

        self::assertContains(
            'AUTH CRAM-MD5',
            $socket->writtenCommands,
        );
    }

    public function testAuthenticateCramMd5ThrowsOnMalformedChallenge(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH CRAM-MD5',
            ],
            additionalResponses: [
                $this->response(334, '@@not-base64@@'),
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::CRAM_MD5,
            username: 'user',
            password: 'pass',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticateCramMd5ThrowsWhenFinalStepRejected(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH CRAM-MD5',
            ],
            additionalResponses: [
                $this->response(334, \base64_encode('<challenge@server>')),
                $this->response(535, 'Bad digest'),
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::CRAM_MD5,
            username: 'user',
            password: 'wrong',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticateXoauth2SucceedsWithProvider(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH XOAUTH2',
            ],
            additionalResponses: [
                $this->response(235, 'Authentication successful'),
            ],
        );

        $session->authenticate(
            mechanism: SmtpAuth::XOAUTH2,
            username: 'user',
            password: '',
            xoauthTokenProvider: new StaticXoauthTokenProvider(
                token: 'ya29.canned',
            ),
        );

        self::assertStringStartsWith(
            'AUTH XOAUTH2 ',
            $socket->writtenCommands[1],
        );
    }

    public function testAuthenticateXoauth2ThrowsWhenProviderMissing(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH XOAUTH2',
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::XOAUTH2,
            username: 'user',
            password: '',
            xoauthTokenProvider: null,
        );
    }

    public function testAuthenticateXoauth2SendsEmptyContinuationOnChallenge(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH XOAUTH2',
            ],
            additionalResponses: [
                $this->response(334, 'server error challenge'),
                $this->response(535, 'Bad token'),
            ],
        );

        try {
            $session->authenticate(
                mechanism: SmtpAuth::XOAUTH2,
                username: 'user',
                password: '',
                xoauthTokenProvider: new StaticXoauthTokenProvider(
                    token: 'ya29.canned',
                ),
            );

            self::fail('Expected MailException');
        } catch (MailException) {
            self::assertContains('', $socket->writtenCommands);
        }
    }

    public function testAuthenticateXoauth2ThrowsWhenNonChallengeFailureReturned(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'AUTH XOAUTH2',
            ],
            additionalResponses: [
                $this->response(535, 'Bad token'),
            ],
        );

        $this->expectException(MailException::class);

        $session->authenticate(
            mechanism: SmtpAuth::XOAUTH2,
            username: 'user',
            password: '',
            xoauthTokenProvider: new StaticXoauthTokenProvider(
                token: 'ya29.canned',
            ),
        );
    }

    public function testResetSendsRsetCommand(): void
    {
        [$session, $socket] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'OK'),
            ],
        );

        $session->reset();

        self::assertContains('RSET', $socket->writtenCommands);
    }

    public function testResetThrowsWhenServerRejects(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(500, 'RSET failed'),
            ],
        );

        $this->expectException(MailException::class);

        $session->reset();
    }

    public function testCloseSendsQuitAndDisconnects(): void
    {
        [$session, $socket] = $this->openSession(
            additionalResponses: [
                $this->response(221, 'Bye'),
            ],
        );

        $session->close();

        self::assertContains('QUIT', $socket->writtenCommands);
        self::assertSame(1, $socket->disconnectCalls);
    }

    public function testCloseIsNoopWhenSocketNotConnected(): void
    {
        $socket = new ScriptedSmtpSocket();
        $session = new SmtpSession(
            socket: $socket,
        );

        $session->close();

        self::assertSame(0, $socket->disconnectCalls);
    }

    public function testCloseSwallowsQuitFailureButStillDisconnects(): void
    {
        [$session, $socket] = $this->openSession();

        $session->close();

        self::assertSame(1, $socket->disconnectCalls);
    }

    public function testEnforceSizeLimitThrowsWhenMessageExceedsAdvertisedSize(): void
    {
        [$session] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'SIZE 10',
            ],
        );

        $this->expectException(MailException::class);

        $session->sendMessage(
            serialized: $this->serialized(
                body: \str_repeat('X', 500),
            ),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );
    }

    public function testSendMessageWithResultClassifiesTransientFailureForFourXxRcptResponse(): void
    {
        [$session] = $this->openSession(
            additionalResponses: [
                $this->response(250, 'MAIL FROM OK'),
                $this->response(450, 'Mailbox busy, try again later'),
            ],
        );

        $outcomes = $session->sendMessageWithResult(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        self::assertCount(1, $outcomes);
        self::assertSame(
            \Tuxxedo\Mail\Result\RecipientStatus::TRANSIENT_FAILURE,
            $outcomes[0]->status,
        );
    }

    public function testAdvertisedSizeLimitReturnsNullWhenParamIsNonNumeric(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'SIZE not-a-number',
            ],
            additionalResponses: [
                $this->response(250, 'OK'),
                $this->response(250, 'OK'),
                $this->response(354, 'Start mail input'),
                $this->response(250, 'Message accepted'),
            ],
        );

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        $mailFromCommand = null;

        foreach ($socket->writtenCommands as $command) {
            if (\str_starts_with($command, 'MAIL FROM:')) {
                $mailFromCommand = $command;

                break;
            }
        }

        self::assertNotNull($mailFromCommand);
        self::assertStringNotContainsString(
            'SIZE=',
            $mailFromCommand,
        );
    }

    public function testBuildMailFromIncludesSizeAndBody8BitMimeWhenAdvertised(): void
    {
        [$session, $socket] = $this->openSession(
            ehloExtensions: [
                'mail.test hello',
                'SIZE 10485760',
                '8BITMIME',
            ],
            additionalResponses: [
                $this->response(250, 'OK'),
                $this->response(250, 'OK'),
                $this->response(354, 'Start mail input'),
                $this->response(250, 'Message accepted'),
            ],
        );

        $session->sendMessage(
            serialized: $this->serialized(),
            envelopeFrom: $this->envelopeFrom(),
            recipients: [
                $this->recipient(),
            ],
        );

        $mailFromCommand = null;

        foreach ($socket->writtenCommands as $command) {
            if (\str_starts_with($command, 'MAIL FROM:')) {
                $mailFromCommand = $command;

                break;
            }
        }

        self::assertNotNull($mailFromCommand);
        self::assertStringContainsString(
            'SIZE=',
            $mailFromCommand,
        );

        self::assertStringContainsString(
            'BODY=8BITMIME',
            $mailFromCommand,
        );
    }
}
