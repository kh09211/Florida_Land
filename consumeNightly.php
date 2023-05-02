<?php
require 'vendor/autoload.php';

use Land\Zillow;
use Carbon\Carbon;
use App\SQLiteConnection;
use App\SQLiteCreateZillowTable;

echo "Executing script...";

$pdo = (new SQLiteConnection())->connect();
if ($pdo == null) { 
	echo 'Whoops, could not connect to the SQLite database!'; die;
} else {
	// create table if it doesn't exist already
	//(new SQLiteCreateZillowTable($pdo))->dropTables();
	(new SQLiteCreateZillowTable($pdo))->createTables();
}

$zillow = new Zillow($pdo);

// execute the scrape and store results
$zillow->consumeLandData('sold');
$zillow->insertListings('sold');
$zillow->consumeLandData('forsale');
$zillow->insertListings('forsale');
exit();
