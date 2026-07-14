@ECHO OFF
CD ../
CALL php85 "vendor/bin/paratest" --processes=auto %*
CD .dev/
