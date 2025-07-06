<?php

declare(strict_types=1);

use App\Services\Logger\Logger;
use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Debug\DebugErrorHandler;
use Tuxxedo\Discovery\Bridges\ComposerDiscoverer;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Kernel\Profile;
use Tuxxedo\Http\Request\Middleware\HttpsRequiredMiddleware;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\StrictTransportSecurityMiddleware;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DynamicRouter;

require_once __DIR__ . '/../vendor/autoload.php';

$app = Kernel::createFromDirectory(
    directory: __DIR__ . '/../app',
);

$app->discover(new ComposerDiscoverer());

// @todo session module
// @todo Escaper module

if ($app->appProfile === Profile::DEBUG) {
    DebugErrorHandler::registerPhpErrorHandler();

    $app->defaultExceptionHandler(
        static fn (): ErrorHandlerInterface => new DebugErrorHandler(),
    );
} else {
    $app->defaultExceptionHandler(
        new class ($app->container) implements ErrorHandlerInterface {
            public function __construct(
                private readonly Container $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                $html = '';
                $html .= '<h2>Exception</h2>';
                $html .= '<pre>';
                $html .= $exception;
                $html .= '</pre>';
                $html .= '<h2>Logger</h2>';

                $logger = $this->container->resolve(LoggerInterface::class);

                if (\sizeof($logger) > 0) {
                    $html .= '<pre>';
                    $html .= $logger->formatEntries();
                    $html .= '</pre>';
                } else {
                    $html .= '<em>No log entries</em>';
                }

                return $response->withBody($html);
            }
        },
    );
}

// @todo Implement loading of app/services.php into $this->container, providers?
$app->container->persistent(Logger::class);

// @todo Once the router is registered, look into the routes and where it retrieve its
//       internal database, which could for example be static, app/routes.php,
//       static attributes (via precompiled file) or dynamic attributes via reflection
$app->router(
    new DynamicRouter(
        container: $app->container,
        directory: __DIR__ . '/../app/Controllers',
        baseNamespace: '\App\Controllers\\',
    ),
);

$app->middleware(
    static fn (): MiddlewareInterface => new StrictTransportSecurityMiddleware(),
);

$app->run();
