@ECHO OFF
CD ../
CALL php85 "vendor/bin/phpunit"
CD .dev/