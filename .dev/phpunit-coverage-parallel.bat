@ECHO OFF
SET XDEBUG_MODE=coverage
CD ../
RD /s /q test-coverage
CALL php85 "vendor/bin/paratest" --processes=auto --coverage-html "test-coverage" --coverage-clover "test-coverage/clover.xml"
CD .dev/
