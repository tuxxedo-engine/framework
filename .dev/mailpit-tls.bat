@ECHO OFF
IF "%MAILPIT_PATH%" == "" (
    EXIT /B 1
)

IF NOT EXIST "%MAILPIT_PATH%" (
    EXIT /B 1
)

"%MAILPIT_PATH%" --smtp=127.0.0.1:1027 --listen=127.0.0.1:8027 --smtp-tls-cert=%~dp0..\tests\Fixture\Mail\tls\mailpit-test.crt --smtp-tls-key=%~dp0..\tests\Fixture\Mail\tls\mailpit-test.key
