@ECHO OFF
CD ../
CALL php85 "vendor/bin/phpstan"
CD .dev/