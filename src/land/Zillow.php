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

	public function getResults() {
		return $this->results;
	}

	public function getCounties() {
		return array_keys($this->countyData);
	}

	public function getDatesAvailable() {
		//$stmt = $this->pdo->query("SELECT DISTINCT(DATE(insert_timestamp, 'unixepoch')) FROM zillow_sold_totals");
		//$datesAvailable = $stmt->fetchall(\PDO::FETCH_COLUMN) ?: [];

		//return $datesAvailable;
	}

	/**
     * consume the API and load land sold results
     */
	public function consumeLandData(string $mode = 'sold') {
		if (!in_array($mode, ['sold', 'forsale'])) throw new Exception('The mode selection can only be "sold" or "forsale" strings.');

		$browser = new HttpBrowser(HttpClient::create());
		foreach ($this->countyData as $county => $data) {
			$this->results[$county] = ['results' => [], 'total' => 0];
			if ($mode == 'sold') {
				// Sold route
				$routeStr = 'https://www.zillow.com/search/GetSearchPageState.htm?searchQueryState={"pagination":{}'.
				',"mapBounds":' . $data['mapState'] .
				',"regionSelection":' . $data['regionSelection'] . 
				',"isMapVisible":true,"filterState":{"isForSaleByAgent":{"value":false},"isForSaleByOwner":{"value":false},"isNewConstruction":{"value":false},"isComingSoon":{"value":false},"isAuction":{"value":false},"isForSaleForeclosure":{"value":false},"isRecentlySold":{"value":true},"sortSelection":{"value":"globalrelevanceex"},"doz":{"value":"90"},"isSingleFamily":{"value":false},"isTownhouse":{"value":false},"isMultiFamily":{"value":false},"isCondo":{"value":false},"isApartment":{"value":false},"isManufactured":{"value":false},"isApartmentOrCondo":{"value":false}},"isListVisible":true}&wants={%22cat1%22:[%22listResults%22,%22mapResults%22]}&requestId=4';
			} else {
				// For sale route
				$routeStr = 'https://www.zillow.com/search/GetSearchPageState.htm?searchQueryState={"pagination":{}'.
				',"mapBounds":' . $data['mapState'] .
				',"regionSelection":' . $data['regionSelection'] . 
				',"isMapVisible":true,"filterState":{"doz":{"value":"90"},"isCondo":{"value":false},"isPreMarketForeclosure":{"value":true},"isApartment":{"value":false},"isMultiFamily":{"value":false},"isAllHomes":{"value":true},"sortSelection":{"value":"days"},"isSingleFamily":{"value":false},"isTownhouse":{"value":false},"isMiddleSchool":{"value":false},"isHighSchool":{"value":false},"includeUnratedSchools":{"value":false},"isManufactured":{"value":false},"isComingSoon":{"value":false},"isPublicSchool":{"value":false},"isPrivateSchool":{"value":false},"isApartmentOrCondo":{"value":false},"isElementarySchool":{"value":false},"isCharterSchool":{"value":false}},"isListVisible":true}&wants={%22cat1%22:[%22listResults%22,%22mapResults%22]}&requestId=4';
			}			
			sleep(rand(5, 10) / 5); // pause between between 1 and 2 s to avoid running too fast
			$browser->request('GET', $routeStr);
			$response = $browser->getResponse();
			$response = $response->toArray();
			$resData = $response['cat1'];
			$results = [...$resData['searchResults']['mapResults'], ...$resData['searchResults']['listResults']];
			// process land results
			foreach ($results as $result) {
				if (!array_key_exists('hdpData', $result) || !array_key_exists('homeInfo', $result['hdpData'])) continue;
				$hdpData = $result['hdpData']['homeInfo'];
				//if ((int)$hdpData['daysOnZillow'] !== -1) { dump($result); die; } // possibly useful for homes, few results for land
				if ($mode == 'sold') {
					if (!empty($hdpData['dateSold']) && !empty($hdpData['price']) && !empty($hdpData['lotAreaValue'])) {
						$this->results[$county]['results'][$result['zpid']] = [
							'date' => (int)substr($hdpData['dateSold'], 0, 10), //(new \DateTime)->setTimestamp((int)substr($hdpData['dateSold'], 0, 10)),
							'price' => (int)$hdpData['price'],
							'acres' => ($hdpData['lotAreaUnit'] == 'acres') ? (float)$hdpData['lotAreaValue'] : (float)$hdpData['lotAreaValue']/43560 // conv to acres
						];
					}
				} else {
					if (!empty($hdpData['price']) && !empty($hdpData['lotAreaValue'])) {
						$this->results[$county]['results'][$result['zpid']] = [
							'daysOnZillow' => !empty($result['timeOnZillow']) ? round((int)substr($result['timeOnZillow'], 0, 6) / 86400) : -1, // -1 means no data
							'price' => (int)$hdpData['price'],
							'acres' => ($hdpData['lotAreaUnit'] == 'acres') ? (float)$hdpData['lotAreaValue'] : (float)$hdpData['lotAreaValue']/43560 // conv to acres
						];
					}
				}
			}
		}
	}

	/**
     * insert sold results into the database
     */
	public function insertListings($mode = 'sold') {
		if ($mode == 'forsale') $this->pdo->prepare("DELETE FROM zillow_forsale")->execute();
		foreach ($this->results as $county => $countyResults) {
			// get out list of zpids to check agains
			$stmt = $this->pdo->query("SELECT zpid FROM zillow_" . $mode);
			$zpids = $stmt->fetchall(\PDO::FETCH_COLUMN) ?: [];
			foreach ($countyResults['results'] as $zpid => $result) {
				if (in_array($zpid, $zpids)) continue;
				if ($mode == 'sold') {
					$sql = "INSERT INTO zillow_sold (insert_timestamp, zpid, date_sold, price, acres, county) VALUES (?,?,?,?,?,?)";
					$this->pdo->prepare($sql)->execute([time(), $zpid, $result['date'], $result['price'], $result['acres'], $county]);
				} else {
					$sql = "INSERT INTO zillow_forsale (insert_timestamp, zpid, days_on_zillow, price, acres, county) VALUES (?,?,?,?,?,?)";
					$this->pdo->prepare($sql)->execute([time(), $zpid, $result['daysOnZillow'], $result['price'], $result['acres'], $county]);
				}
			}
		}
	}

	/**
     * get the sold results from the database
     */
    public function getSold($days) {
		$startUnix = time() - ($days * 86400);
		$endUnix = time();
        $stmt = $this->pdo->query("SELECT *
                                   FROM zillow_sold
                                   WHERE date_sold BETWEEN $startUnix AND $endUnix
                                   ORDER BY date_sold");
		return $stmt->fetchall(\PDO::FETCH_ASSOC) ?? [];
    }

	/**
     * get sold land rows ordered by county
     */
    public function getSoldByCounty(int $days = 90) {
		$counties = array_keys($this->countyData);
		foreach ($counties as $county) {
			$resultsByCounty[$county] = [];
		}
		$results = $this->getSold($days);
		foreach ($results as $result) {
			$resultsByCounty[$result['county']][] = $result;
		}
		ksort($resultsByCounty);
		return $resultsByCounty;
    }
	
	/**
     * get the forsale results from the database
     */
    public function getForSale() {
        $stmt = $this->pdo->query("SELECT *
                                   FROM zillow_forsale
                                   ORDER BY days_on_zillow");
		return $stmt->fetchall(\PDO::FETCH_ASSOC) ?? [];
    }

	/**
     * get land forsale rows ordered by county
     */
    public function getForSaleByCounty() {
		$counties = array_keys($this->countyData);
		foreach ($counties as $county) {
			$resultsByCounty[$county] = [];
		}
		$results = $this->getForSale();
		foreach ($results as $result) {
			$resultsByCounty[$result['county']][] = $result;
		}
		ksort($resultsByCounty);
		return $resultsByCounty;
    }
}
