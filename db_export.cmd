echo off
echo Database version on Windows using SVN - Copyright (C) 2013 Christopher Pattle
echo This program comes with ABSOLUTELY NO WARRANTY;
echo This is free software, and you are welcome to redistribute it under certain conditions;
echo Please see the LICENSE.txt file that came with this program
pause
::Set the root directory and the path to this application
set rootdir=C:PATH\TO\ROOT
set apppath=C:\PATH\TO\ROOT\database

::Change to the root directory and get the svn version number
cd %rootdir%

for /f "delims=: tokens=1,2" %%a in ('svn info') do (
  if "%%a"=="Revision" (
    set /a svnrevision=%%b
  )
)

::Set the global variables we'll need
set /a revision=%svnrevision%+1
set database=db_name
set tables=table1 table2 table3
set host=localhost
set /p user=Enter database username for %database%:
set /p passwd=Enter database password for %database%:

::Change to the application directory
cd %apppath%

::Check to see if the tables directory exists
::If it doesn't exist this is probably the first time we are dumping the database
IF EXIST tables goto revisionloop
mkdir tables

:revisionloop
IF EXIST tables\%revision% (
    setlocal enableextensions enabledelayedexpansion
    set /a i=2
    :innerloop
    if exist tables\%revision%_!i! (
        set /a i=i+1
        goto :innerloop
    )
    set revision=%revision%_!i!
)

:tableloop
::Loop through all of the tables in the database
for %%f in (%tables%) do call :processing %%f
goto endpoint


:processing
set temp=%~n1

::Dump the database structure
IF EXIST tables/%database%.%temp%.sql goto new_structure
mysqldump -u%user% -p%passwd% -h%host% --no_data=true --skip-dump-date %database% %temp%  >tables/%database%.%temp%.sql
goto skip

:new_structure
mysqldump -u%user% -p%passwd% -h%host% --no_data=true --skip-dump-date %database% %temp%  >tables/%database%.%temp%.%revision%.sql

::Check to see if the database structure has changed
fc /b %apppath%\tables\%database%.%temp%.sql %apppath%\tables\%database%.%temp%.%revision%.sql > nul

::If the database structure hasn't changed then delete the new structure we made and just keep the old one
if errorlevel 1 goto files_differ
del tables\%database%.%temp%.%revision%.sql
goto skip

:files_differ
::Make a directory for this revision number to store the new db structure in
IF EXIST tables\%revision% goto nextstep
mkdir tables\%revision%

:nextstep
::Copy the old and new structure to the revision number folder
copy %apppath%\tables\%database%.%temp%.sql tables\%revision%\%database%.%temp%.sql
copy %apppath%\tables\%database%.%temp%.%revision%.sql tables\%revision%\%database%.%temp%.%revision%.sql

::Update the core structure with the new version
del tables\%database%.%temp%.sql
ren %apppath%\tables\%database%.%temp%.%revision%.sql %database%.%temp%.sql

goto skip

:endpoint
IF EXIST tables\%revision% goto generate_diff

:generate_diff

::Call the PHP script to work out the sql diff
php generate_diff.php %revision%

::Once we have generated all the sql we now need to commit anything in the tables folder
svn add tables --force
svn ci -m "Updated database structures"

:skip
