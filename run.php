<?php
/*
 * PHP: Recursively Backup Files & Folders to ZIP-File
 * (c) 2012-2014: Marvin Menzerath - http://menzerath.eu
*/

// Make sure the script can handle large folders/files
ini_set('max_execution_time', 600);
ini_set('memory_limit','1024M');

require_once ("config_info.php");
$hostname = $dbinfo["host"];
$user = $dbinfo["user"];
$password = $dbinfo["pass"];
$dbname = $dbinfo["dbname"];

// Start the backup!



// backup_tables($hostname,$user,$password,$dbname);
if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/sql')) {
			mkdir($_SERVER['DOCUMENT_ROOT'].'/sql', 0777, true);
		}
	$path=$_SERVER['DOCUMENT_ROOT'].'/sql/backup_'.date("d-m-y").'.sql';
exec("mysqldump -u $user -p$password $dbname > $path");

echo "dtatbase backup done";




zip($_SERVER['DOCUMENT_ROOT'].'/start', 'start/backup_'.date("d-m-y").'.zip');
echo 'Finished.';
	
// Here the magic happens :)
 function zip($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }
 
        $zip = new ZipArchive();
        if(!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }
 
        $source = str_replace('\\', DIRECTORY_SEPARATOR, realpath($source));
        $source = str_replace('/', DIRECTORY_SEPARATOR, $source);
 
        if(is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
 
            foreach ($files as $file) {
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
                $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
 
                if ($file == '.' || $file == '..' || empty($file) || $file==DIRECTORY_SEPARATOR) continue;
                // Ignore "." and ".." folders
                if ( in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1), array('.', '..')) )
                    continue;
 
                $file = realpath($file);
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
                $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
 
                if (is_dir($file) === true) {
                    $d = str_replace($source . DIRECTORY_SEPARATOR, '', $file );
                    if(empty($d)) continue;
                    print "Making DIRECTORY {$d}<Br>";
                    $zip->addEmptyDir($d);
                } elseif (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . DIRECTORY_SEPARATOR, '', $file), file_get_contents($file));
                } else {
                    // do nothing
                }
            }
        } elseif (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }
 
        return $zip->close();
		
		
    }
	
	
	//create a backup and saving  the sql file in directory
	function backup_tables($host,$user,$pass,$name,$tables = '*')
	{
		$return = "";
		$link = mysql_connect($host,$user,$pass);
		mysql_select_db($name,$link);
		//get all of the tables
		if($tables == '*')
		{
		$tables = array();
		$result = mysql_query('SHOW TABLES');
		while($row = mysql_fetch_row($result))
		{
		$tables[] = $row[0];
		}
		}
		else
		{
		$tables = is_array($tables) ? $tables : explode(',',$tables);
		}
		//cycle through
		foreach($tables as $table)
		{
		$result = mysql_query('SELECT * FROM '.$table);
		$num_fields = mysql_num_fields($result);
		$return.= 'DROP TABLE '.$table.';';
		$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
		$return.= "\n\n".$row2[1].";\n\n";
		for ($i = 0; $i < $num_fields; $i++)
		{
		while($row = mysql_fetch_row($result))
		{
		$return.= 'INSERT INTO '.$table.' VALUES(';
		for($j=0; $j<$num_fields; $j++)
		{
		$row[$j] = addslashes($row[$j]);
		$row[$j] = ereg_replace("\n","\\n",$row[$j]);
		if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
		if ($j<($num_fields-1)) { $return.= ','; }
		}
		$return.= ");\n";
		}
		}
		$return.="\n\n\n";
		}
		//create a directory if not exist
		if (!file_exists('start/sql')) {
			mkdir('start/sql', 0777, true);
		}
		//save file
		$handle = fopen('start/sql/db-backup-'.date("y-m-d").'.sql','w+');
		// echo $return;
		fwrite($handle,$return);
		fclose($handle);
	}
	
	
	
	
?>