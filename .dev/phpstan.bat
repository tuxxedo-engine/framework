@ECHO OFF
CD ../
CALL php84 "vendor/bin/phpstan"
CD .dev/