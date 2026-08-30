@ECHO OFF
IF "%MAILPIT_PATH%" == "" (
    EXIT /B 1
)

IF NOT EXIST "%MAILPIT_PATH%" (
    EXIT /B 1
)

"%MAILPIT_PATH%" --smtp=127.0.0.1:1026 --listen=127.0.0.1:8026 --smtp-auth-accept-any --smtp-auth-allow-insecure
