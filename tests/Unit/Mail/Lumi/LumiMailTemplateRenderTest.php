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

namespace Unit\Mail\Lumi;

use PHPUnit\Framework\TestCase;
use Support\Mail\StubCurrencyDenominatedTemplateMessage;
use Support\Mail\StubLocalizedTemplateMessage;
use Support\Mail\StubTemplateMessage;
use Support\Mail\StubTimeZonedTemplateMessage;
use Tuxxedo\Container\Container;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Lumi\Config\LumiMailTemplateConfig;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailTemplateRenderInterface;
use Tuxxedo\Mail\Message;
use Tuxxedo\Temporal\TimeZone;

class LumiMailTemplateRenderTest extends TestCase
{
    private string $templatesDir;
    private string $cacheDir;
    private int $initialBufferLevel = 0;

    /**
     * @var list<string>
     */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->initialBufferLevel = \ob_get_level();
        $this->templatesDir = $this->createTempDirectory('templates');
        $this->cacheDir = $this->createTempDirectory('cache');
    }

    protected function tearDown(): void
    {
        while (\ob_get_level() > $this->initialBufferLevel) {
            \ob_end_clean();
        }

        foreach (\array_reverse($this->tempPaths) as $path) {
            $this->removeRecursive($path);
        }

        $this->tempPaths = [];
    }

    private function createTempDirectory(
        string $prefix,
    ): string {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo_mail_template_' . $prefix . '_');

        if ($path === false) {
            throw new \RuntimeException('Failed to allocate a temporary path');
        }

        \unlink($path);
        \mkdir($path, 0755, true);

        $normalized = \str_replace('\\', '/', $path);
        $this->tempPaths[] = $normalized;

        return $normalized;
    }

    private function removeRecursive(
        string $path,
    ): void {
        if (!\is_dir($path)) {
            if (\is_file($path)) {
                @\unlink($path);
            }

            return;
        }

        $entries = \glob($path . '/*');

        if (\is_array($entries)) {
            foreach ($entries as $entry) {
                $this->removeRecursive($entry);
            }
        }

        @\rmdir($path);
    }

    private function writeTemplate(
        string $name,
        string $contents,
    ): void {
        \file_put_contents(
            $this->templatesDir . '/' . $name . '.lumi',
            $contents,
        );
    }

    private function createRender(): MailTemplateRenderInterface
    {
        return (new LumiMailTemplateConfig(
            directory: $this->templatesDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
            alwaysCompile: true,
        ))->createTemplateRender(
            container: $this->makeContainer(),
        );
    }

    private function makeContainer(): Container
    {
        $container = new Container();
        $container->singleton($container);

        return $container;
    }

    public function testRenderSetsRenderedBodyOnReturnedMessage(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: 'Hi {{ userName }}!',
        );

        $render = $this->createRender();

        $result = $render->render(
            message: new StubTemplateMessage(
                userName: 'kalle',
            ),
        );

        self::assertSame(
            'Hi kalle!',
            $result->body,
        );
    }

    public function testRenderUsesTheLocaleOfALocalizedMessage(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: '{{ locale().tag }}',
        );

        $render = $this->createRender();

        self::assertSame(
            'pt-BR',
            $render->render(
                message: new StubLocalizedTemplateMessage(
                    locale: Locale::parse('pt-BR'),
                ),
            )->body,
        );

        self::assertSame(
            'en-US',
            $render->render(
                message: new StubTemplateMessage(),
            )->body,
        );
    }

    public function testRenderHumanizesInTheLocaleOfALocalizedMessage(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: '{{ date_duration(7200) }}',
        );

        self::assertSame(
            '2 timer',
            $this->createRender()->render(
                message: new StubLocalizedTemplateMessage(
                    locale: Locale::parse('da-DK'),
                ),
            )->body,
        );
    }

    public function testRenderUsesTheCurrencyOfACurrencyDenominatedMessage(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: '{{ 1234.5 | currency }}',
        );

        self::assertSame(
            '$1,234.50',
            $this->createRender()->render(
                message: new StubCurrencyDenominatedTemplateMessage(
                    currency: 'USD',
                ),
            )->body,
        );
    }

    public function testRenderUsesTheTimeZoneOfATimeZonedMessage(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: '{{ date(\'H:i\', 0) }}',
        );

        self::assertSame(
            '01:00',
            $this->createRender()->render(
                message: new StubTimeZonedTemplateMessage(
                    timeZone: TimeZone::parse('Europe/Copenhagen'),
                ),
            )->body,
        );
    }

    public function testRenderSetsBodyTypeFromAttribute(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: 'body',
        );

        $render = $this->createRender();

        $result = $render->render(
            message: new StubTemplateMessage(),
        );

        self::assertSame(
            BodyType::HTML,
            $result->bodyType,
        );
    }

    public function testRenderThrowsWhenMessageHasNoMailTemplateAttribute(): void
    {
        $render = $this->createRender();

        try {
            $render->render(
                message: new Message(
                    from: new Address(
                        email: 'from@example.com',
                    ),
                    to: [
                        new Address(
                            email: 'to@example.com',
                        ),
                    ],
                    subject: 'Plain',
                ),
            );

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                Message::class,
                $exception->getMessage(),
            );
        }
    }

    public function testRenderWrapsUnderlyingViewExceptionAsMailException(): void
    {
        $render = $this->createRender();

        try {
            $render->render(
                message: new StubTemplateMessage(),
            );

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                'stub',
                $exception->getMessage(),
            );
        }
    }

    public function testConfigWithAlwaysCompileDisabledStillProducesRender(): void
    {
        $render = (new LumiMailTemplateConfig(
            directory: $this->templatesDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
            alwaysCompile: false,
        ))->createTemplateRender(
            container: $this->makeContainer(),
        );

        self::assertInstanceOf(
            MailTemplateRenderInterface::class,
            $render,
        );
    }

    public function testConfigWithErrorReportingEnabledStillProducesRender(): void
    {
        $render = (new LumiMailTemplateConfig(
            directory: $this->templatesDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
            disableErrorReporting: false,
        ))->createTemplateRender(
            container: $this->makeContainer(),
        );

        self::assertInstanceOf(
            MailTemplateRenderInterface::class,
            $render,
        );
    }
}
