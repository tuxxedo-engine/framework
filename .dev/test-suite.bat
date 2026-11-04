@ECHO OFF
SET DEV=%~dp0
SET DEV=%DEV:~0,-1%
CALL wt -w new new-tab --title Engine -d "%DEV%" cmd /k ; split-pane -V -s 0.35 --title Mailpit -d "%DEV%" cmd /k mailpit.bat ; split-pane -H -s 0.75 --title "Mailpit auth" -d "%DEV%" cmd /k mailpit-auth.bat ; split-pane -H -s 0.6667 --title "Mailpit TLS" -d "%DEV%" cmd /k mailpit-tls.bat ; split-pane -H -s 0.5 --title Redis -d "%DEV%" cmd /k redis.bat ; move-focus left
