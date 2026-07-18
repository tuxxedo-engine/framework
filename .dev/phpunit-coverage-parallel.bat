@ECHO OFF
SET XDEBUG_MODE=coverage
CD ../
RD /s /q test-coverage
CALL php85 -d memory_limit=-1 "vendor/bin/paratest" --processes=auto --display-warnings --display-notices --display-deprecations --display-errors --display-phpunit-deprecations --display-skipped --coverage-html "test-coverage" --coverage-clover "test-coverage/clover.xml"
CD .dev/
