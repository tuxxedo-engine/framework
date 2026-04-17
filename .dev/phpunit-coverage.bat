@ECHO OFF
SET XDEBUG_MODE=coverage
CD ../
RD /s /q test-coverage
CALL php85 "vendor/bin/phpunit" --testsuite Unit --coverage-html "test-coverage" --coverage-clover "test-coverage/clover.xml"
CD .dev/