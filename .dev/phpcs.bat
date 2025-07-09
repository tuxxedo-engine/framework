@ECHO OFF
CD ../
CALL php84 "vendor/bin/php-cs-fixer" check --verbose --config=.php-cs-fixer.php --allow-risky=yes --diff
CD .dev/