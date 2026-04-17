@ECHO OFF
CD ../
CALL php85 "vendor/bin/phpunit" --testsuite Unit %*
CD .dev/