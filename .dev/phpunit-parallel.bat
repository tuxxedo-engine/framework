@ECHO OFF
CD ../
CALL php85 "vendor/bin/paratest" --processes=auto --display-warnings --display-notices --display-deprecations --display-errors --display-phpunit-deprecations --display-skipped %*
CD .dev/
