<?php

/*
Database version on Windows using SVN. This program helps to version database structures when developing on Windows and using SVN
Copyright (C) 2013  Christopher Pattle

This program has been released under the WTFPL (http://www.wtfpl.net/) so you can do what ever you want with it.  

However if you find it useful please feel free to send me a £10 note to 98 Regent Street, Stowmarket, Suffolk, 1P14 1AS, UK
 */

//Get the revisions number
//This was passed as an argument from the batch file
$revision = $argv[1];

//Loop through all of the files with the new database structure in them
foreach (glob('tables/' . $revision . '/*.' . $revision . '.sql') as $filename)
{
    $oldSqlFile = $filename;
    $newSqlFile = str_replace('.' . $revision, '', $filename);
    
    //Get the contents of the file
    $aNewSql = file($oldSqlFile);
    $aOldSql = file($newSqlFile);
    
    //Get the table name
    $tableName = str_replace('tables/' . $revision . '/', '', $filename);
    $aFileParts = explode('.', $tableName);
    
    //Filter the arrays so we just have the create table statements
    $aNewSql = filterSql($aNewSql);
    $aOldSql = filterSql($aOldSql);

    //Get the difference
    list($aUpDiff, $aDownDiff) = createDiff($aNewSql, $aOldSql, $aFileParts[1]);
    
    //Implode the array to get a string of SQL commands
    $sqlUpDiff = implode("\n", $aUpDiff);
    $sqlDownDiff = implode("\n", $aDownDiff);

    //Put the contents back in the file
    file_put_contents($oldSqlFile, $sqlUpDiff);
    file_put_contents($newSqlFile, $sqlDownDiff);
    
    copy($oldSqlFile, str_replace('tables/' . $revision, 'tables/' . $revision . '/up', $oldSqlFile));
    copy($newSqlFile, str_replace('tables/' . $revision, 'tables/' . $revision . '/down', $newSqlFile));
    
    //Delete the file containing the old structure
    unlink($oldSqlFile);
    unlink($newSqlFile);
}

/*
 * filterSql()
 * Function to filter comments, drop table statements and white space out of a sql file
 * 
 * @author Chris Pattle
 * 
 * @param $aSQL ARRAY An array containing each line of the sql file
 * 
 * @return $aNewSql Returns the array with all the things we don't want stripped out
 */
function filterSql($aSql)
{
    //Create an array to store the filter sql in
    $aNewSql = array();
    
    //Check to see if the array has anything in it
    if(is_array($aSql) && !empty($aSql))
    {
        $count = 0;
        
        //Loop through the lines in the sql file
        foreach($aSql as $sqlLine)
        {
            //Strip out any comments
            $sqlLine =  trim(preg_replace('![\t\r\n]|(--[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/;)!', '', $sqlLine));
            
            //Strip out any DROP TABLE statements
            if(substr($sqlLine, 0, 10) == 'DROP TABLE')
                  $sqlLine = '';
            
            //Strip out the ENGINE type, AUTO INCREMENT and other bits we don't need
            if(substr($sqlLine, 0, 8) == ') ENGINE')
                  $sqlLine = '';
            
            //We haven't strip out this line then add it to the array
            if($sqlLine != '')
                $aNewSql[] = $sqlLine;
            
            $count++;
        }
    }
    
    return $aNewSql;
}

/*
 * createDiff()
 * Function to get the difference between the two arrays of sql and turn them into useable sql commands
 * 
 * @author Chris Pattle
 * 
 * @param $aNewSql ARRAY An array containing the new create table sql
 * @param $aOldSql ARRAY An array containing the old create table sql
 * @param $tableName STRING The table name the create table sql is for
 * 
 * @return $aDiff Returns an array of sql commands to that the user can run to 
 * get their table structure up to date
 */
function createDiff($aNewSql, $aOldSql, $tableName)
{
    $aUpDiff = array();
    $aDownDiff = array();
    
    $count = 0;
    
    //Check to see if the array with the new sql in is bigger than the old sql
    //This would mean fieds have been added
    //Otherwise it means fields have been deleted
    if(count($aNewSql) > count($aOldSql))
    {
        //Loop through the old sql
        foreach($aOldSql as $sqlLine)
        {
            $match = FALSE;

            while($match === FALSE)
            {
                //Check to see if this line in the old sql matches the one in the new sql
                if($sqlLine != $aNewSql[$count])
                {
                    //If it does then it means this is a new field
                    //So we need to add the sql to add this field to the array
                    preg_match("/`(.*)`/", $aNewSql[($count - 1)], $aPrevMatch);
                    $aUpDiff[] = 'ALTER TABLE ' . $tableName . ' ADD ' . str_replace(',', '', $aNewSql[$count]) . ' AFTER ' . $aPrevMatch[0] . ';';
                    
                    preg_match("/`(.*)`/", $aNewSql[$count], $aThisMatch);
                    $aDownDiff[] = 'ALTER TABLE ' . $tableName . ' DROP ' . $aThisMatch[0] . ';';
                }
                else
                {
                    $match = TRUE;
                }
                
                $count++;
            }
        }
    }
    else
    {
        //Loop through the new sql
        foreach($aNewSql as $sqlLine)
        {            
            $match = FALSE;

            while($match === FALSE)
            {
                //Check to see if this line in the new sql matches the one in the old sql
                if($sqlLine != $aOldSql[$count])
                {
                    //If it doesn't then it means this field has been dropped
                    //So we need to add the sql to drop this field into the array
                    preg_match("/`(.*)`/", $aOldSql[$count], $aThisMatch);
                    $aUpDiff[] = 'ALTER TABLE ' . $tableName . ' DROP ' . $aThisMatch[0] . ';';
                    
                    preg_match("/`(.*)`/", $aOldSql[($count - 1)], $aPrevMatch);
                    $aDownDiff[] = 'ALTER TABLE ' . $tableName . ' ADD ' . str_replace(',', '', $aOldSql[$count]) . ' AFTER ' . $aPrevMatch[0] . ';';
                }
                else
                {
                    $match = TRUE;
                }
                
                $count++;
            }
        }
    }
    
    return array($aUpDiff, $aDownDiff);
}
?>
