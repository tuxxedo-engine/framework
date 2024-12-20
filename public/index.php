<?php

declare(strict_types=1);

namespace
{
    use Tuxxedo\Application\Application;
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Application\ApplicationState;
    use Tuxxedo\Config\ConfigInterface;
    use Tuxxedo\Container\Container;
    use Tuxxedo\Container\Resolvers\App;
    use Tuxxedo\Container\Resolvers\AppName;
    use Tuxxedo\Container\Resolvers\AppVersion;
    use Tuxxedo\Container\Resolvers\AppState;
    use Tuxxedo\Container\Resolvers\AppContainer;
    use Tuxxedo\Container\Resolvers\ConfigValue;

    require_once __DIR__ . '/../vendor/autoload.php';

    class AppInfo
    {
        public function __construct(
            #[App] public readonly Application $app,
        ) {
        }
    }

    $app = ApplicationFactory::createFromDirectory(
        directory: __DIR__ . '/../app',
    );

    echo '<pre>';
    var_dump(
        $app->container->resolve(AppInfo::class)->app->container->resolve($app::class)->appState,
    );
    echo '</pre>';
}
