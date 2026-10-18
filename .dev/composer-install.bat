@ECHO OFF
CD ../
CALL composer85 install
CALL composer85 dump-autoload -o --classmap-authoritative
CD .dev/