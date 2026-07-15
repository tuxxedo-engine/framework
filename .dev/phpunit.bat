@ECHO OFF
CD ../
CALL php85 "vendor/bin/phpunit" --display-warnings --display-notices --display-deprecations --display-errors --display-phpunit-deprecations --display-skipped %*
CD .dev/
