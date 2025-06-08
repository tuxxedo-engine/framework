@ECHO OFF
CD ../
SET PHP_CS_FIXER_IGNORE_ENV=1 && CALL php84 "vendor/bin/php-cs-fixer" fix --verbose --config=.php-cs-fixer.php --allow-risky=yes --diff
CD .dev/
