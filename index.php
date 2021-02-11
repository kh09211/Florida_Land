<?php
require 'vendor/autoload.php';

// the index file where we will show our craiglist results

use Land\Craigslist;
use Carbon\Carbon;

$citiesArray = [
	'daytona',
	'keys',
	'fortmyers',
	'gainesville',
	'cfl',
	'jacksonville',
	'lakeland',
	'lakecity',
	'ocala',
	'okaloosa',
	'orlando',
	'panamacity',
	'pensacola',
	'sarasota',
	'miami',
	'spacecoast',
	'staugustine',
	'tallahassee',
	'tampa',
	'treasure'
];

if (isset($_GET['run'])) {

	$craigslist = new Craigslist;
	$allCities24hrs = [];


	foreach ($citiesArray as $city) {
		$random = rand(1, 10) / 5; // a random number between 0.2 and 2
		sleep($random); // pause to avoid running too fast

		// execute the scrape
		$results = $craigslist->GetTodaysLand($city);

		// merge it with the rest of the results
		$allCities24hrs = array_merge($allCities24hrs, $results);
	}

	// use usort an callback to sort the array by timestamps
	function cmp($a, $b)
	{
		$parsedA = Carbon::parse($a['time']);
		$parsedB = Carbon::parse($b['time']);

		return ($parsedA->lessThan($parsedB)) ? 1 : -1;
	}

	usort($allCities24hrs, 'cmp');


	// filter duplicate ad postings out of the array, then filter out null values, then reset the index values
	$allCities24hrs = array_filter($allCities24hrs, function ($item, $key) use ($allCities24hrs) {
		if ($key > 0) {
			return ($item['desc'] != $allCities24hrs[$key - 1]['desc']);
		} else {
			return true; // the first iteration
		}
	}, ARRAY_FILTER_USE_BOTH);

	$allCities24hrs = array_values(array_filter($allCities24hrs));

	$adsCount = "Found: " . strval(count($allCities24hrs));
} else {
	$adsCount = implode(' - ', array_map(function ($val) {
		return ucfirst($val);
	}, $citiesArray));
}

?>

<!DOCTYPE html>
<html>

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<!-- title -->
	<title>Florida Craigslist Land Compiler 1.0</title>
	<!-- bootsrtap 5 CDN -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
</head>

<body class="bg-light">
	<div class="container">
		<h1 class="text-center mt-5 text-success">Florida Land Craglist Ads</h1>
		<h3 class="text-center text-success">past 24 hrs, newest first.</h3>
		<h4 class="text-center mt-3"><?php echo $adsCount ?></h4>
		<br>

		<?php if (isset($_GET['run'])) { ?>
			<div class="border border-success rounded">
				<table class="table table-success table-striped">
					<thead>
						<tr>
							<th scope="col">#</th>
							<th scope="col">Time</th>
							<th scope="col">City</th>
							<th scope="col">Description</th>
							<th scope="price">Price</th>
							<th scope="col">Link</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($allCities24hrs as $index => $listing) {

							echo '<tr>';
							echo '<th scope="row">' . strval($index + 1) . '</th>';
							echo '<td>' . $listing['time'] . '</td>';
							echo '<td>' . $listing['city'] . '</td>';
							echo '<td>' . $listing['desc'] . '</td>';
							echo '<td>' . $listing['price'] . '</td>';
							echo '<td><a href="' . $listing['link'] . '" target="_blank">Ad Link</a></td>';
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		<?php } else { ?>
			<form action="/PHP_Projects/florida_land/index.php" method="GET">
				<input type="hidden" name="run" value="true" />
				<div class="w-100">
					<button class="d-block mx-auto btn btn-success" onclick="showLoading()">Run Search!</button>
					<div class="text-center"></div>
				</div>
				<div id="loading" class="my-5 text-center h2"></div>
			</form>
		<?php } ?>
	</div>
</body>
<script>
	function showLoading() {
		loadingDiv = document.getElementById('loading');
		loadingDiv.innerHTML = "Loading results...";
	}
</script>

</html>