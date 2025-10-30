@ECHO OFF
SET XDEBUG_MODE=coverage
CD ../
RD /s /q test-coverage
CALL php84 "vendor/bin/phpunit" --coverage-html "test-coverage"
CD .dev/