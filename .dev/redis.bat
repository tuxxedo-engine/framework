@ECHO OFF

IF "%REDIS_PATH%" == "" (
    EXIT /B 1
)

IF NOT EXIST "%REDIS_PATH%" (
    EXIT /B 1
)

"%REDIS_PATH%" --bind 127.0.0.1 --port 6379 --save "" --appendonly no %*
