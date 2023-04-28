<?php

namespace Land;

//use Goutte\Client;
//use Carbon\Carbon;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class Craigslist {

	public function getTodaysLand($city) {
		/* -- NOTE -- this code is deprecated, Craigslist has changed their site to use API calls in place of static generated data
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
		*/
		$cityCodeMap = [
			'daytona' => '238',
			'keys' => '330',
			'fortmyers' => '125',
			'gainesville' => '219',
			'cfl' => '639',
			'jacksonville' => '80',
			'lakeland' => '376',
			'lakecity' => '638',
			'ocala' => '333',
			'okaloosa' => '640',
			'orlando' => '39',
			'panamacity' => '562',
			'pensacola' => '203',
			'sarasota' => '237',
			'miami' => '20',
			'spacecoast' => '331',
			'staugustine' => '557',
			'tallahassee' => '186',
			'tampa' => '37',
			'treasure' => '332'
		];
		$browser = new HttpBrowser(HttpClient::create());
		/*
		$unixTimestamp = time();
		$unixTimestamp24hr = $unixTimestamp - 86400;
		var_dump($unixTimestamp24hr); die;
		*/
		$browser->request('GET', "https://sapi.craigslist.org/web/v8/postings/search/full?batch={$cityCodeMap[$city]}-0-360-0-0&postedToday=1&cc=US&lang=en&purveyor=owner&searchPath=rea");
		$response = $browser->getResponse()->toArray();
		$data = $response['data'];
		$resultCount = $data['totalResultCount'];
		$items = array_slice($data['items'], 0, $resultCount);

		$results = [];
		foreach ($items as $item) {
			foreach (array_reverse($item) as $value) {
				if (gettype($value) == 'string') {
					$desc = $value;
					break;
				}
			}
			$slug = (gettype($item[6]) == 'array' && count($item[6]) == 2) ? $item[6][1] : $item[5][1];
			$priceFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
			$price = $priceFormatter->formatCurrency($item[3], 'USD');
			$results[] = [
				'city' => ucfirst($city),
				'link' => "https://{$city}.craigslist.org/reo/d/{$slug}/{$data['decode']['minPostingId']}.html",
				'desc' => $desc,
				'price' => ($item[3] !== -1) ? substr($price, 0, -3) : 'Unavailable'
			];
		}

		return $results;
	}

}