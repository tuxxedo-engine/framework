@ECHO OFF
CD ../
CALL composer85 update
CALL composer85 dump-autoload -o --classmap-authoritative
CD .dev/