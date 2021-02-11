<?php

namespace Land;

use Goutte\Client;
use Carbon\Carbon;

class Craigslist {

	public function getTodaysLand($city) {
	
		// create a goutte client instance
		$client= new Client();

		// go to the craigslist website
		$crawler = $client->request('GET', 'https://' . $city . '.craigslist.org/search/reo?housing_type=12');

		$resultsArr = [];
		$results = $crawler->filter('div.result-info')
			->each(function ($parentCrowler) use ($city) {
				$time = $parentCrowler->filter('time')->extract(['datetime']);

				// check and see if posting is more than 24 hours old
				$nowMinus24 = Carbon::now()->subDay();
				$postingTime = Carbon::parse($time[0]);
				if ($postingTime->greaterThanOrEqualTo($nowMinus24)) {
				// posting is less than 24 hours old, grab it

					$info = $parentCrowler->filter('h3.result-heading > a')->extract(['href', '_text']);
					$price = $parentCrowler->filter('.result-price')->text();

					$link = $info[0][0];
					$desc = $info[0][1];
					return [
						'city' => $city,
						'link' => $link,
						'desc' => $desc,
						'price' => $price,
						'time' => $time[0]
					];
				}
			});

		// remove any null array values and reorder then return
		return array_values(array_filter($results));	
	}

}