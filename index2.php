<?php
require 'vendor/autoload.php';

// the index file where we will show our craiglist results

use Land\Zillow;
use Carbon\Carbon;
use App\SQLiteConnection;
use App\SQLiteCreateZillowTable;

$pdo = (new SQLiteConnection())->connect();
if ($pdo == null) { 
	echo 'Whoops, could not connect to the SQLite database!'; die;
}

$zillow = new Zillow($pdo);
$countiesArray = $zillow->getCounties();
$zillow->getDatesAvailable();
$projectPath = '/';//"/PHP_Projects/florida_land/";

// Variables to pass to the view
$adsCount = implode(' - ', array_map(function ($val) {
	return ucfirst($val);
}, $countiesArray));

if (isset($_GET['daterange'])) {
	// set dates to get from db
} else {
	$dateTo = (new \DateTime)->setTimestamp($soldTotals[0]['insert_timestamp'])->format('m/d/Y');
	$dateFrom = (new \DateTime)->setTimestamp($soldTotals[0]['insert_timestamp'] - 7776000)->format('m/d/Y');
}

//$soldTotals = $zillow->getTotalsSold();
//$forSaleTotals = $zillow->getTotalsForSale();
$forSaleListings = $zillow->getForSaleByCounty();
$soldListings = $zillow->getSoldByCounty();
$totalListingsInDB = array_reduce([...$forSaleListings, ...$soldListings],fn($a, $v) => $a + count($v), 0);

//soldTotalsJSON = json_encode($soldTotals);
//$forSaleTotalsJSON = json_encode($forSaleTotals);
$soldListingsJSON = json_encode($soldListings);
$forSaleListingsJSON = json_encode($forSaleListings);

echo "<script>
		const soldListings = {$soldListingsJSON};
		const forSaleListings = {$forSaleListingsJSON};
		</script>";
?>

<!DOCTYPE html>
<html>
	<head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- title -->
		<title>Florida Land Zillow Stats 1.0</title>
		<!-- bootsrtap 5 CDN -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
		<style>
			body {
				background-image: url('field2.jpg');
				background-repeat: no-repeat;
				background-attachment: fixed;
				background-position: center top;
				background-size: cover;
			}
			.chart-bg {
				background-color: white;
				opacity: .8;
				border-radius: 5px;
				padding: 8 10 3 10;
			}
		</style>
	</head>

	<body class="bg-light pb-5">
		<div class="container">
			<h1 class="text-center mt-5 text-success">Florida Land Sales Stats</h1>
			<h3 class="text-center text-success">With publically available data from Zillow.com</h3>
			<h4 class="text-center text-success"><?php echo "{$dateFrom} - {$dateTo}" ?></h4>
			<!-- <h6 class="text-center mt-4 text-success"><?php echo $adsCount ?></h6> -->
			<div class="mt-4 pt-2">
				<div class="row pb-4">
					<div class="col-12 col-lg-6">
						<canvas class="chart-bg" id="mostForSaleChart"></canvas>
					</div>
					<div class="col-12 col-lg-6">
						<canvas class="chart-bg" id="lowestDaysOnZillowChart"></canvas>
						<canvas class="chart-bg" id="highestDaysOnZillowChart"></canvas>
					</div>
				</div>
				<div class="row pb-4">
					<div class="col-12">
						<canvas class="chart-bg" style="height: 2400px;" id="allForSaleChart"></canvas>
					</div>
				</div>
				<div class="row pb-4">
					<div class="col-12 col-lg-6">
						<canvas class="chart-bg" id="highestCostPerAcreChart"></canvas>
					</div>
					<div class="col-12 col-lg-6">
						<canvas class="chart-bg" id="lowestCostPerAcreChart"></canvas>
					</div>
				</div>
				<div class="row pb-4">
					<div class="col-12 col-lg-6">
						<canvas class="chart-bg" id="highestMedSalePriceChart"></canvas>
					</div>
					<div class="col-12 col-lg-6">
						<canvas class="chart-bg" id="lowestMedSalePriceChart"></canvas>
					</div>
				</div>
			</div>
		</div>

		<!-- load the charts.js CDN -->
		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

		<!-- load our zillow chart code -->
		<script src="zillowCharts.js"></script>
	</body>
	
</html>