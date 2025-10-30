@ECHO OFF
CD ../
CALL php84 "vendor/bin/phpunit"
CD .dev/