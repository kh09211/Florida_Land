<?php

namespace Land;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Land\ZillowCountyData;

class Zillow {

	private $results = [];
	private $countyData;
	private $pdo;

	public function __construct($pdo) {
		$this->countyData = ZillowCountyData::$countyData;
		$this->pdo = $pdo;
	}

	/**
     * consume the API and load results
     */
	public function consumeLandData() {
		$browser = new HttpBrowser(HttpClient::create());
		foreach ($this->countyData as $county => $data) {
			$this->results[$county] = [];
			$routeStr = 'https://www.zillow.com/search/GetSearchPageState.htm?searchQueryState={"pagination":{}'.
				',"mapBounds":' . $data['mapState'] .
				',"regionSelection":' . $data['regionSelection'] . 
				',"isMapVisible":true,"filterState":{"isForSaleByAgent":{"value":false},"isForSaleByOwner":{"value":false},"isNewConstruction":{"value":false},"isComingSoon":{"value":false},"isAuction":{"value":false},"isForSaleForeclosure":{"value":false},"isRecentlySold":{"value":true},"sortSelection":{"value":"globalrelevanceex"},"doz":{"value":"30"},"isSingleFamily":{"value":false},"isTownhouse":{"value":false},"isMultiFamily":{"value":false},"isCondo":{"value":false},"isApartment":{"value":false},"isManufactured":{"value":false},"isApartmentOrCondo":{"value":false}},"isListVisible":true}&wants={%22cat1%22:[%22listResults%22,%22mapResults%22]}&requestId=4';
			sleep(rand(5, 20) / 10); // pause between between 0.5 and 1 s to avoid running too fast
			$browser->request('GET', $routeStr);
			$response = $browser->getResponse();
			$response = $response->toArray();
			$resData = $response['cat1'];
			$results = [...$resData['searchResults']['mapResults'], ...$resData['searchResults']['listResults']];
			// process map results
			foreach ($results as $result) {
				$hdpData = $result['hdpData']['homeInfo'];
				if ((int)$hdpData['daysOnZillow'] !== -1) { dump($result); die; }
				if (!empty(trim($hdpData['dateSold'])) && !empty(trim($hdpData['price'])) && !empty(trim($hdpData['lotAreaValue']))) {
					$this->results[$county]['results'][$result['zpid']] = [
						'date' => (int)substr($hdpData['dateSold'], 0, 10), //(new \DateTime)->setTimestamp((int)substr($hdpData['dateSold'], 0, 10)),
						'price' => (int)$hdpData['price'],
						'acres' => ($hdpData['lotAreaUnit'] == 'acres') ? (float)$hdpData['lotAreaValue'] : (float)$hdpData['lotAreaValue']/43560 // conv to acres
					];
				}
			}
		}
	}

	/**
     * insert results into the database
     */
	public function insertSold() {
		// get out list of zpids to check agains
		$stmt = $this->pdo->query("SELECT zpid FROM zillow");
		$zpids = $stmt->fetchall(\PDO::FETCH_COLUMN) ?: [];
		foreach ($this->results as $county => $countyResults) {
			foreach ($countyResults['results'] as $zpid => $result) {
				if (in_array($zpid, $zpids)) continue;
				$sql = "INSERT INTO zillow (insert_timestamp, zpid, date_sold, price, acres, county) VALUES (?,?,?,?,?,?)";
				$this->pdo->prepare($sql)->execute([time(), $zpid, $result['date'], $result['price'], $result['acres'], $county]);
			}
		}
	}

	/**
     * get the results from the database
     */
    public function getSold(int $days = 30) {
		$startUnix = time() - ($days * 86400);
		$endUnix = time();
        $stmt = $this->pdo->query("SELECT *
                                   FROM zillow
                                   WHERE date_sold BETWEEN $startUnix AND $endUnix
                                   ORDER BY date_sold");
		return $stmt->fetchall(\PDO::FETCH_ASSOC) ?? [];
    }

	public function getResults() {
		return $this->results;
	}

	public function getCounties() {
		return array_keys($this->countyData);
	}
}