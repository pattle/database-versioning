database-versioning
===================

Database versioning on Windows using SVN

For this program to work you must be developing on Windows and you must be using SVN to version files
You must have a SVN command line client installed so you can use SVN from the Windows command line

Unzip the contents into a folder called "database"

Next open up the db_export.cmd file and replace the "rootdir", "apppath", "database", "tables" and "host" variables

Before you commit your code changes run the db_export.cmd file and it will create the SQL for any database changes you have made and will put them in a folder named after the next revision number
