@ECHO OFF
CALL composer-install
CALL phpcsfix
CALL phpstan