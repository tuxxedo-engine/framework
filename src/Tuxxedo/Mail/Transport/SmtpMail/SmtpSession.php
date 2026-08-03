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

namespace Tuxxedo\Mail\Transport\SmtpMail;

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\SmtpMail\Xoauth\XoauthTokenProviderInterface;

class SmtpSession
{
    private const int SMTP_GREETING_CODE = 220;
    private const int SMTP_STARTTLS_READY_CODE = 220;
    private const int SMTP_AUTH_CHALLENGE_CODE = 334;
    private const int SMTP_AUTH_SUCCESS_CODE = 235;
    private const int SMTP_DATA_PROMPT_CODE = 354;

    public private(set) SmtpCapabilities $capabilities;

    public function __construct(
        private readonly SmtpSocketInterface $socket = new SmtpSocket(),
    ) {
        $this->capabilities = new SmtpCapabilities();
    }

    /**
     * @throws MailException
     */
    public function open(
        string $host,
        int $port,
        SmtpTls $tls,
        int $connectTimeout,
        int $readTimeout,
        bool $verifyPeer,
        ?string $caFile,
        string $ehloDomain,
        ?string $unixSocket = null,
    ): void {
        $this->socket->connect(
            host: $host,
            port: $port,
            tls: $tls,
            connectTimeout: $connectTimeout,
            readTimeout: $readTimeout,
            verifyPeer: $verifyPeer,
            caFile: $caFile,
            unixSocket: $unixSocket,
        );

        $greeting = $this->socket->readResponse();

        if ($greeting->code !== self::SMTP_GREETING_CODE) {
            throw MailException::fromSmtpUnexpectedGreeting(
                code: $greeting->code,
                summary: $greeting->summary,
            );
        }

        $this->handshake($ehloDomain);

        if ($tls === SmtpTls::STARTTLS && $unixSocket === null) {
            $this->negotiateStartTls($ehloDomain);
        }
    }

    /**
     * @param list<AddressInterface> $recipients
     *
     * @throws MailException
     */
    public function sendMessage(
        SerializedMessageInterface $serialized,
        AddressInterface $envelopeFrom,
        array $recipients,
    ): void {
        $size = \strlen($serialized->wire);

        $this->enforceSizeLimit(
            size: $size,
        );

        $mailFrom = $this->buildMailFromCommand(
            envelopeFrom: $envelopeFrom,
            size: $size,
        );

        if ($this->capabilities->supports('PIPELINING')) {
            $this->sendMessagePipelined(
                serialized: $serialized,
                mailFrom: $mailFrom,
                recipients: $recipients,
            );

            return;
        }

        $this->sendMessageSequential(
            serialized: $serialized,
            mailFrom: $mailFrom,
            recipients: $recipients,
        );
    }

    /**
     * @param list<AddressInterface> $recipients
     *
     * @throws MailException
     */
    private function sendMessageSequential(
        SerializedMessageInterface $serialized,
        string $mailFrom,
        array $recipients,
    ): void {
        $this->sendCommand(
            command: $mailFrom,
        );

        foreach ($recipients as $recipient) {
            $this->sendCommand(
                command: \sprintf('RCPT TO:<%s>', $recipient->email),
            );
        }

        $this->sendData($serialized);
    }

    /**
     * @param list<AddressInterface> $recipients
     *
     * @throws MailException
     */
    private function sendMessagePipelined(
        SerializedMessageInterface $serialized,
        string $mailFrom,
        array $recipients,
    ): void {
        $commands = [
            $mailFrom,
        ];

        foreach ($recipients as $recipient) {
            $commands[] = \sprintf('RCPT TO:<%s>', $recipient->email);
        }

        $this->socket->writeRaw(
            bytes: \implode("\r\n", $commands) . "\r\n",
        );

        $firstFailure = null;

        foreach ($commands as $command) {
            $response = $this->socket->readResponse();

            if (!$response->isSuccess && $firstFailure === null) {
                $firstFailure = MailException::fromSmtpCommandRejected(
                    command: $command,
                    code: $response->code,
                    summary: $response->summary,
                );
            }
        }

        if ($firstFailure !== null) {
            throw $firstFailure;
        }

        $this->sendData($serialized);
    }

    /**
     * @throws MailException
     */
    private function sendData(
        SerializedMessageInterface $serialized,
    ): void {
        $this->socket->writeCommand('DATA');
        $dataResponse = $this->socket->readResponse();

        if ($dataResponse->code !== self::SMTP_DATA_PROMPT_CODE) {
            throw MailException::fromSmtpCommandRejected(
                command: 'DATA',
                code: $dataResponse->code,
                summary: $dataResponse->summary,
            );
        }

        $stuffed = \rtrim(self::dotStuff($serialized->wire), "\r\n") . "\r\n";
        $this->socket->writeRaw($stuffed);
        $this->socket->writeRaw(".\r\n");

        $bodyResponse = $this->socket->readResponse();

        if (!$bodyResponse->isSuccess) {
            throw MailException::fromSmtpCommandRejected(
                command: 'DATA (body)',
                code: $bodyResponse->code,
                summary: $bodyResponse->summary,
            );
        }
    }

    /**
     * @param list<AddressInterface> $recipients
     * @return list<RecipientOutcome>
     *
     * @throws MailException
     */
    public function sendMessageWithResult(
        SerializedMessageInterface $serialized,
        AddressInterface $envelopeFrom,
        array $recipients,
    ): array {
        $size = \strlen($serialized->wire);

        $this->enforceSizeLimit(
            size: $size,
        );

        $mailFrom = $this->buildMailFromCommand(
            envelopeFrom: $envelopeFrom,
            size: $size,
        );

        $this->socket->writeCommand($mailFrom);
        $mailFromResponse = $this->socket->readResponse();

        if (!$mailFromResponse->isSuccess) {
            return self::applyStatusToAll(
                recipients: $recipients,
                response: $mailFromResponse,
            );
        }

        $outcomes = [];
        $acceptedIndexes = [];

        foreach ($recipients as $recipient) {
            $this->socket->writeCommand(
                command: \sprintf('RCPT TO:<%s>', $recipient->email),
            );

            $response = $this->socket->readResponse();

            if ($response->isSuccess) {
                $outcomes[] = new RecipientOutcome(
                    recipient: $recipient,
                    status: RecipientStatus::ACCEPTED,
                    code: $response->code,
                    summary: $response->summary,
                );
                $acceptedIndexes[] = \sizeof($outcomes) - 1;

                continue;
            }

            $outcomes[] = new RecipientOutcome(
                recipient: $recipient,
                status: self::classify($response),
                code: $response->code,
                summary: $response->summary,
            );
        }

        if ($acceptedIndexes === []) {
            return $outcomes;
        }

        $this->socket->writeCommand('DATA');
        $dataResponse = $this->socket->readResponse();

        if ($dataResponse->code !== self::SMTP_DATA_PROMPT_CODE) {
            return self::overrideAt(
                outcomes: $outcomes,
                indexes: $acceptedIndexes,
                response: $dataResponse,
            );
        }

        $stuffed = \rtrim(self::dotStuff($serialized->wire), "\r\n") . "\r\n";
        $this->socket->writeRaw($stuffed);
        $this->socket->writeRaw(".\r\n");

        $bodyResponse = $this->socket->readResponse();

        if (!$bodyResponse->isSuccess) {
            return self::overrideAt(
                outcomes: $outcomes,
                indexes: $acceptedIndexes,
                response: $bodyResponse,
            );
        }

        return $outcomes;
    }

    /**
     * @param list<AddressInterface> $recipients
     * @return list<RecipientOutcome>
     */
    private static function applyStatusToAll(
        array $recipients,
        SmtpResponse $response,
    ): array {
        $status = self::classify($response);
        $outcomes = [];

        foreach ($recipients as $recipient) {
            $outcomes[] = new RecipientOutcome(
                recipient: $recipient,
                status: $status,
                code: $response->code,
                summary: $response->summary,
            );
        }

        return $outcomes;
    }

    /**
     * @param list<RecipientOutcome> $outcomes
     * @param list<int> $indexes
     * @return list<RecipientOutcome>
     */
    private static function overrideAt(
        array $outcomes,
        array $indexes,
        SmtpResponse $response,
    ): array {
        $status = self::classify($response);

        foreach ($indexes as $index) {
            $outcomes[$index] = new RecipientOutcome(
                recipient: $outcomes[$index]->recipient,
                status: $status,
                code: $response->code,
                summary: $response->summary,
            );
        }

        return \array_values($outcomes);
    }

    private static function classify(
        SmtpResponse $response,
    ): RecipientStatus {
        if ($response->isPermanentFailure) {
            return RecipientStatus::PERMANENT_FAILURE;
        }

        return RecipientStatus::TRANSIENT_FAILURE;
    }

    /**
     * @throws MailException
     */
    public function authenticate(
        SmtpAuth $mechanism,
        string $username,
        #[\SensitiveParameter]
        string $password,
        ?XoauthTokenProviderInterface $xoauthTokenProvider,
    ): void {
        if ($mechanism === SmtpAuth::NONE) {
            return;
        }

        $wireName = $mechanism->value;

        if (!$this->supportsAuthMechanism($wireName)) {
            throw MailException::fromSmtpAuthMechanismNotAdvertised(
                mechanism: $wireName,
            );
        }

        match ($mechanism) {
            SmtpAuth::PLAIN => $this->authPlain($username, $password),
            SmtpAuth::LOGIN => $this->authLogin($username, $password),
            SmtpAuth::CRAM_MD5 => $this->authCramMd5($username, $password),
            SmtpAuth::XOAUTH2 => $this->authXoauth2($username, $xoauthTokenProvider),
        };
    }

    /**
     * @throws MailException
     */
    public function reset(): void
    {
        $this->sendCommand(
            command: 'RSET',
        );
    }

    public function close(): void
    {
        if (!$this->socket->isConnected) {
            return;
        }

        try {
            $this->socket->writeCommand('QUIT');
            (void) $this->socket->readResponse();
        } catch (\Throwable) {
        } finally {
            $this->socket->disconnect();
        }
    }

    /**
     * @throws MailException
     */
    private function handshake(
        string $ehloDomain,
    ): void {
        $this->socket->writeCommand(
            command: 'EHLO ' . $ehloDomain,
        );

        $ehloResponse = $this->socket->readResponse();

        if ($ehloResponse->isSuccess) {
            $this->capabilities = SmtpCapabilities::parse(
                lines: $ehloResponse->lines,
            );

            return;
        }

        $this->socket->writeCommand(
            command: 'HELO ' . $ehloDomain,
        );

        $heloResponse = $this->socket->readResponse();

        if (!$heloResponse->isSuccess) {
            throw MailException::fromSmtpHelloRejected(
                code: $heloResponse->code,
                summary: $heloResponse->summary,
            );
        }

        $this->capabilities = new SmtpCapabilities();
    }

    /**
     * @throws MailException
     */
    private function negotiateStartTls(
        string $ehloDomain,
    ): void {
        if (!$this->capabilities->supports('STARTTLS')) {
            throw MailException::fromSmtpStartTlsNotAdvertised();
        }

        $this->socket->writeCommand(
            command: 'STARTTLS',
        );

        $response = $this->socket->readResponse();

        if ($response->code !== self::SMTP_STARTTLS_READY_CODE) {
            throw MailException::fromSmtpCommandRejected(
                command: 'STARTTLS',
                code: $response->code,
                summary: $response->summary,
            );
        }

        $this->socket->enableCrypto();
        $this->handshake($ehloDomain);
    }

    /**
     * @throws MailException
     */
    private function sendCommand(
        string $command,
    ): void {
        $this->socket->writeCommand(
            command: $command,
        );

        $response = $this->socket->readResponse();

        if (!$response->isSuccess) {
            throw MailException::fromSmtpCommandRejected(
                command: $command,
                code: $response->code,
                summary: $response->summary,
            );
        }
    }

    /**
     * @throws MailException
     */
    private function authPlain(
        string $username,
        #[\SensitiveParameter] string $password,
    ): void {
        $payload = \base64_encode("\0" . $username . "\0" . $password);
        $this->socket->writeCommand(
            command: 'AUTH PLAIN ' . $payload,
        );

        $response = $this->socket->readResponse();

        if ($response->code !== self::SMTP_AUTH_SUCCESS_CODE) {
            throw MailException::fromSmtpAuthFailed(
                mechanism: SmtpAuth::PLAIN->value,
                code: $response->code,
                summary: $response->summary,
            );
        }
    }

    /**
     * @throws MailException
     */
    private function authLogin(
        string $username,
        #[\SensitiveParameter] string $password,
    ): void {
        $this->socket->writeCommand(
            command: 'AUTH LOGIN',
        );

        $this->expectChallenge(
            mechanism: SmtpAuth::LOGIN->value,
            response: $this->socket->readResponse(),
        );

        $this->socket->writeCommand(
            command: \base64_encode($username),
        );

        $this->expectChallenge(
            mechanism: SmtpAuth::LOGIN->value,
            response: $this->socket->readResponse(),
        );

        $this->socket->writeCommand(
            command: \base64_encode($password),
        );

        $final = $this->socket->readResponse();

        if ($final->code !== self::SMTP_AUTH_SUCCESS_CODE) {
            throw MailException::fromSmtpAuthFailed(
                mechanism: SmtpAuth::LOGIN->value,
                code: $final->code,
                summary: $final->summary,
            );
        }
    }

    /**
     * @throws MailException
     */
    private function authCramMd5(
        string $username,
        #[\SensitiveParameter]
        string $password,
    ): void {
        $this->socket->writeCommand(
            command: 'AUTH CRAM-MD5',
        );

        $challengeResponse = $this->socket->readResponse();
        $this->expectChallenge(
            mechanism: SmtpAuth::CRAM_MD5->value,
            response: $challengeResponse,
        );

        $encodedChallenge = $challengeResponse->summary;
        $challenge = \base64_decode($encodedChallenge, true);

        if ($challenge === false) {
            throw MailException::fromSmtpAuthMalformedChallenge(
                challenge: $encodedChallenge,
            );
        }

        $digest = \hash_hmac('md5', $challenge, $password);
        $reply = \base64_encode($username . ' ' . $digest);

        $this->socket->writeCommand(
            command: $reply,
        );

        $final = $this->socket->readResponse();

        if ($final->code !== self::SMTP_AUTH_SUCCESS_CODE) {
            throw MailException::fromSmtpAuthFailed(
                mechanism: SmtpAuth::CRAM_MD5->value,
                code: $final->code,
                summary: $final->summary,
            );
        }
    }

    /**
     * @throws MailException
     */
    private function authXoauth2(
        string $username,
        ?XoauthTokenProviderInterface $xoauthTokenProvider,
    ): void {
        if ($xoauthTokenProvider === null) {
            throw MailException::fromSmtpXoauth2ProviderMissing();
        }

        $token = $xoauthTokenProvider->getToken();
        $payload = \base64_encode(
            \sprintf(
                "user=%s\x01auth=Bearer %s\x01\x01",
                $username,
                $token,
            ),
        );

        $this->socket->writeCommand(
            command: 'AUTH XOAUTH2 ' . $payload,
        );

        $response = $this->socket->readResponse();

        if ($response->code === self::SMTP_AUTH_SUCCESS_CODE) {
            return;
        }

        if ($response->code === self::SMTP_AUTH_CHALLENGE_CODE) {
            $this->socket->writeCommand(
                command: '',
            );

            $final = $this->socket->readResponse();

            throw MailException::fromSmtpAuthFailed(
                mechanism: SmtpAuth::XOAUTH2->value,
                code: $final->code,
                summary: $final->summary,
            );
        }

        throw MailException::fromSmtpAuthFailed(
            mechanism: SmtpAuth::XOAUTH2->value,
            code: $response->code,
            summary: $response->summary,
        );
    }

    private function supportsAuthMechanism(
        string $mechanism,
    ): bool {
        $advertised = $this->capabilities->getParams('AUTH');

        foreach ($advertised as $entry) {
            if (\strcasecmp($entry, $mechanism) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws MailException
     */
    private function expectChallenge(
        string $mechanism,
        SmtpResponse $response,
    ): void {
        if ($response->code === self::SMTP_AUTH_CHALLENGE_CODE) {
            return;
        }

        throw MailException::fromSmtpAuthFailed(
            mechanism: $mechanism,
            code: $response->code,
            summary: $response->summary,
        );
    }

    private static function dotStuff(
        string $body,
    ): string {
        return \preg_replace('/(^|\r\n)\./', '$1..', $body) ?? $body;
    }

    /**
     * @throws MailException
     */
    private function enforceSizeLimit(
        int $size,
    ): void {
        $limit = $this->advertisedSizeLimit();

        if ($limit === null || $limit === 0) {
            return;
        }

        if ($size > $limit) {
            throw MailException::fromSmtpMessageTooLarge(
                size: $size,
                limit: $limit,
            );
        }
    }

    private function buildMailFromCommand(
        AddressInterface $envelopeFrom,
        int $size,
    ): string {
        $command = \sprintf('MAIL FROM:<%s>', $envelopeFrom->email);

        if ($this->advertisedSizeLimit() !== null) {
            $command .= ' SIZE=' . $size;
        }

        if ($this->capabilities->supports('8BITMIME')) {
            $command .= ' BODY=8BITMIME';
        }

        return $command;
    }

    private function advertisedSizeLimit(): ?int
    {
        $params = $this->capabilities->getParams('SIZE');

        if ($params === []) {
            return null;
        }

        if (\preg_match('/^\d+$/', $params[0]) !== 1) {
            return null;
        }

        return (int) $params[0];
    }
}
