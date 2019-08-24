<?php


// MySQL host
$mysql_host = $argv[1];
// MySQL username
$mysql_username = $argv[2];
// MySQL password
$mysql_password = $argv[3];
// Database name
$mysql_database = $argv[4];

// Name of the file
$file = $argv[5];

$zip = new ZipArchive;
$res = $zip->open($file);
if ($res === TRUE) {
	$zip->extractTo('./');
	$zip->close();
}
else{
	echo "Can't unzip";
	die();
}

// Create connection
$conn = mysqli_connect($mysql_host, $mysql_username, $mysql_password);

// Check connection
if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}
echo "MYSQL Connected successfully\n";

ini_set('memory_limit',-1);

// Temporary variable, used to store current query
$templine = '';
// Read in entire file
$lines = file('lendo_ebdb.sql');
// Loop through each line
foreach ($lines as $line) {
	// Skip it if it's a comment
	if (substr($line, 0, 2) == '--' || $line == '')
		continue;

	// Add this line to the current segment
	$templine .= $line;
	// If it has a semicolon at the end, it's the end of the query
	if (substr(trim($line), -1, 1) == ';') {
		// Perform the query
		mysqli_query($conn, $templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
		// Reset temp variable to empty
		$templine = '';
	}
}
unlink('lendo_ebdb.sql');
ini_set('memory_limit','128M');
echo "Tables imported successfully\n";