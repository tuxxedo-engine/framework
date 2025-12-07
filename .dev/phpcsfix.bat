@ECHO OFF
CD ../
CALL php85 "vendor/bin/php-cs-fixer" fix --verbose --config=.php-cs-fixer.php --allow-risky=yes --diff
CD .dev/
