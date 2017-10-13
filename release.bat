copy /y  README.md       ..\master\
copy /y  composer.json   ..\master\

del /f /s /q ..\master\src\*.*
xcopy /y /s  src         ..\master\src\

del /f /s /q ..\master\docs\*.*
xcopy /y  docs           ..\master\docs\

pause