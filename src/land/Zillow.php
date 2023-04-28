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

	/****************** SOLD METHODS ******************/

	/**
     * consume the API and load land sold results
     */
	public function consumeLandDataSold() {
		$browser = new HttpBrowser(HttpClient::create());
		foreach ($this->countyData as $county => $data) {
			$this->results[$county] = ['results' => [], 'total' => 0];
			// Route gets all the land sold within the county for the past 30 days
			$routeStr = 'https://www.zillow.com/search/GetSearchPageState.htm?searchQueryState={"pagination":{}'.
				',"mapBounds":' . $data['mapState'] .
				',"regionSelection":' . $data['regionSelection'] . 
				',"isMapVisible":true,"filterState":{"isForSaleByAgent":{"value":false},"isForSaleByOwner":{"value":false},"isNewConstruction":{"value":false},"isComingSoon":{"value":false},"isAuction":{"value":false},"isForSaleForeclosure":{"value":false},"isRecentlySold":{"value":true},"sortSelection":{"value":"globalrelevanceex"},"doz":{"value":"90"},"isSingleFamily":{"value":false},"isTownhouse":{"value":false},"isMultiFamily":{"value":false},"isCondo":{"value":false},"isApartment":{"value":false},"isManufactured":{"value":false},"isApartmentOrCondo":{"value":false}},"isListVisible":true}&wants={%22cat1%22:[%22listResults%22,%22mapResults%22]}&requestId=4';
			sleep(rand(5, 20) / 10); // pause between between 0.5 and 1 s to avoid running too fast
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
				if (!empty($hdpData['dateSold']) && !empty($hdpData['price']) && !empty($hdpData['lotAreaValue'])) {
					$this->results[$county]['results'][$result['zpid']] = [
						'date' => (int)substr($hdpData['dateSold'], 0, 10), //(new \DateTime)->setTimestamp((int)substr($hdpData['dateSold'], 0, 10)),
						'price' => (int)$hdpData['price'],
						'acres' => ($hdpData['lotAreaUnit'] == 'acres') ? (float)$hdpData['lotAreaValue'] : (float)$hdpData['lotAreaValue']/43560 // conv to acres
					];
				}
			}
			// process land totals
			$this->results[$county]['total'] = $resData['searchList']['totalResultCount'];
		}
	}

	/**
     * insert sold results into the database
     */
	public function insertSold() {
		foreach ($this->results as $county => $countyResults) {
			// get out list of zpids to check agains
			$stmt = $this->pdo->query("SELECT zpid FROM zillow_sold");
			$zpids = $stmt->fetchall(\PDO::FETCH_COLUMN) ?: [];
			foreach ($countyResults['results'] as $zpid => $result) {
				if (in_array($zpid, $zpids)) continue;
				$sql = "INSERT INTO zillow_sold (insert_timestamp, zpid, date_sold, price, acres, county) VALUES (?,?,?,?,?,?)";
				$this->pdo->prepare($sql)->execute([time(), $zpid, $result['date'], $result['price'], $result['acres'], $county]);
			}
		}
		// insert the total counts from zillow
		foreach ($this->results as $county => $countyResults) {
			$sql = "INSERT INTO zillow_sold_totals (insert_timestamp, county, total) VALUES (?,?,?)";
			$this->pdo->prepare($sql)->execute([time(), $county, $countyResults['total']]);
		}
	}

	/**
     * get the sold results from the database
     */
    public function getSold(int $days = 30) {
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
    public function getSoldByCounty(int $days = 30) {
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
     * get the sold county totals from the database
     */
    public function getTotalsSold(int $days = 30) {
		$startUnix = time() - ($days * 86400);
		$endUnix = time();
        $stmt = $this->pdo->query("SELECT *
                                   FROM zillow_sold_totals
                                   WHERE insert_timestamp BETWEEN $startUnix AND $endUnix
                                   ORDER BY insert_timestamp");
		return $stmt->fetchall(\PDO::FETCH_ASSOC) ?? [];
    }


	/****************** FOR SALE METHODS ******************/


	/**
     * consume the API and load for sale results
     */
	public function consumeLandDataForSale() {
		$browser = new HttpBrowser(HttpClient::create());
		foreach ($this->countyData as $county => $data) {
			$this->results[$county] = ['results' => [], 'total' => 0];
			// Route gets all the land forsale within the county for the past 30 days
			$routeStr = 'https://www.zillow.com/search/GetSearchPageState.htm?searchQueryState={"pagination":{}'.
				',"mapBounds":' . $data['mapState'] .
				',"regionSelection":' . $data['regionSelection'] . 
				',"isMapVisible":true,"filterState":{"doz":{"value":"90"},"isCondo":{"value":false},"isPreMarketForeclosure":{"value":true},"isApartment":{"value":false},"isMultiFamily":{"value":false},"isAllHomes":{"value":true},"sortSelection":{"value":"days"},"isSingleFamily":{"value":false},"isTownhouse":{"value":false},"isMiddleSchool":{"value":false},"isHighSchool":{"value":false},"includeUnratedSchools":{"value":false},"isManufactured":{"value":false},"isComingSoon":{"value":false},"isPublicSchool":{"value":false},"isPrivateSchool":{"value":false},"isApartmentOrCondo":{"value":false},"isElementarySchool":{"value":false},"isCharterSchool":{"value":false}},"isListVisible":true}&wants={%22cat1%22:[%22listResults%22,%22mapResults%22]}&requestId=4';
			sleep(rand(5, 20) / 10); // pause between between 0.5 and 1 s to avoid running too fast
			$browser->request('GET', $routeStr);
			$response = $browser->getResponse();
			$response = $response->toArray();
			$resData = $response['cat1'];
			$results = [...$resData['searchResults']['mapResults']/*, ...$resData['searchResults']['listResults']*/];
			// process land results
			foreach ($results as $result) {
				if (!array_key_exists('hdpData', $result) || !array_key_exists('homeInfo', $result['hdpData'])) continue;
				$hdpData = $result['hdpData']['homeInfo'];
				//if ((int)$hdpData['daysOnZillow'] !== -1) { dump($result); die; } // possibly useful for homes, few results for land
				if (!empty($result['timeOnZillow']) && !empty($hdpData['price']) && !empty($hdpData['lotAreaValue'])) {
					$this->results[$county]['results'][$result['zpid']] = [
						'daysOnZillow' => (time() - (int)substr($result['timeOnZillow'], 0, 10)) / 86400,
						'price' => (int)$hdpData['price'],
						'acres' => ($hdpData['lotAreaUnit'] == 'acres') ? (float)$hdpData['lotAreaValue'] : (float)$hdpData['lotAreaValue']/43560 // conv to acres
					];
				}
			}
			// process land totals
			$this->results[$county]['total'] = $resData['searchList']['totalResultCount'];
		}
	}

	/**
     * insert forsale results into the database
     */
	public function insertForSale() {
		// clear the database to make way for the latest data
		$this->pdo->prepare("DELETE FROM zillow_forsale")->execute();
		foreach ($this->results as $county => $countyResults) {
			// get out list of zpids to check agains
			$stmt = $this->pdo->query("SELECT zpid FROM zillow_forsale");
			$zpids = $stmt->fetchall(\PDO::FETCH_COLUMN) ?: [];
			foreach ($countyResults['results'] as $zpid => $result) {
				if (in_array($zpid, $zpids)) continue;
				$sql = "INSERT INTO zillow_forsale (insert_timestamp, zpid, days_on_zillow, price, acres, county) VALUES (?,?,?,?,?,?)";
				$this->pdo->prepare($sql)->execute([time(), $zpid, $result['daysOnZillow'], $result['price'], $result['acres'], $county]);
			}
		}
		// insert the total counts from zillow
		foreach ($this->results as $county => $countyResults) {
			$sql = "INSERT INTO zillow_forsale_totals (insert_timestamp, county, total) VALUES (?,?,?)";
			$this->pdo->prepare($sql)->execute([time(), $county, $countyResults['total']]);
		}
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

	/**
     * get the forsale county totals from the database
     */
    public function getTotalsForSale(int $days = 30) {
        $stmt = $this->pdo->query("SELECT *
                                   FROM zillow_forsale_totals
                                   WHERE insert_timestamp
                                   ORDER BY insert_timestamp");
		return $stmt->fetchall(\PDO::FETCH_ASSOC) ?? [];
    }

}