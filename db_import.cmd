echo off
echo Database version on Windows using SVN - Copyright (C) 2013 Christopher Pattle
echo This program comes with ABSOLUTELY NO WARRANTY;
echo This is free software, and you are welcome to redistribute it under certain conditions;
echo Please see the LICENSE.txt file that came with this program
pause

::Use set local to make sure variables are only valid for this import
SetLocal

set apppath=C:\PATH\TO\APPLICATION
set database=db_name
set host=localhost
set /p user=Enter database username for %database%:
set /p passwd=Enter database password for %database%:
set /p revision=Revision number to update your database too (use "current" for the latest):
set direction=up

if "%revision%"=="current" goto :skipdirection
set /p direction=Do you want to migrate "up" or "down" to this revision?:

:skipdirection
if "%direction%"=="up" goto :startimport
if "%direction%"=="down" goto :startimport
echo You need to enter either "up" or "down" for the direction.  Please try again
pause

:startimport
::Change to the application directory
cd %apppath%

::Loop through all of the folders in the tables directory
for /D /r %%a in ("tables\*") do call :sqlloop %%a
EndLocal
pause
exit

:sqlloop
set folder=%~n1

::Check to see if the folder number is gtr that the revision number we want to update too
::If it is then we don't want to process any of the SQL files in that folder
if %folder% gtr %revision% if %revision% neq "current" goto :skipimport

::Loop through the SQL files in the directory
for /f %%b in ('dir /b /s "%apppath%\tables\%folder%\%direction%\*.sql"') do call :import %%b

:import
set temp=%~n1

::Before we try to import the sql in the file check to see the file name variable isn't empty
::If its empty we'll just skip the import
IF "%temp%"=="" goto :skipimport
mysql -u%user% -p%passwd% -h %host% -D %database% --force < %apppath%\tables\%folder%\%direction%\%temp%.sql
:skipimport
