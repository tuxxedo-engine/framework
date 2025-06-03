@ECHO OFF
CD ../
CALL composer84 install
CD .dev/
CALL php84 -f patch-composer.php "php84"