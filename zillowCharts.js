/**
 *  Charts for use with the Zillow stats for Florida land project
 */

// variables for use in some charts

let counties = Object.keys(soldListings);

// ordered by county
let orderedSold = counties.map(county => ({'county': county, 'total': soldListings[county].length}))
let orderedForSale = counties.map(county => ({'county': county, 'total': forSaleListings[county].length}))

// get a group of totals for out big all counties chart
let groupedTotalsByCounty = {};
orderedSold.forEach(data => {
    groupedTotalsByCounty[data.county] = {sold: data};
});
orderedForSale.forEach(data => {
    groupedTotalsByCounty[data.county].forSale = data;
});

// now order by greatest total to lowest
orderedSold.sort((a,b) => a.total <= b.total ? 1 : -1);
orderedForSale.sort((a,b) => a.total <= b.total ? 1 : -1);

/**
 *  Chart for all land for sale / sold in Florida
 */
const allForSaleChart = document.getElementById('allForSaleChart');
new Chart(allForSaleChart, {
    type: 'bar',
    data: {
        labels: Object.keys(groupedTotalsByCounty),
        datasets: [
        {
            label: '# of Lots For Sale',
            data: Object.values(groupedTotalsByCounty).map(data => data.forSale.total),
            borderWidth: 1,
            barThickness: 15,
            backgroundColor: "gold",
        },
        {
            label: '# of Lots Sold',
            data: Object.values(groupedTotalsByCounty).map(data => data.sold.total),
            borderWidth: 1,
            barThickness: 15,
            backgroundColor: "dodgerblue"
        }]
    },
    options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    autoSkip: false,
                }
            }/*,
            x: {
                max: 2000
            }*/
        },
        plugins: {
            title: {
                display: true,
                text: 'Land For Sale / Sold (90 days) by County',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart (Pie) for counties with most sold land
 */
const mostSoldChart = document.getElementById('mostSoldChart');
let mostForSalePie = orderedSold.slice(0, 6);
mostForSalePie.push({county: 'All other counties', total: orderedSold.slice(6).reduce((accumulator, currentValue) => accumulator + currentValue.total, 0)})
new Chart(mostSoldChart, {
    type: 'pie',
    data: {
        labels: mostForSalePie.map(row => row.county),
        datasets: [{
            label: '# of Lots',
            data: mostForSalePie.map(row => row.total),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Lots Sold (90 days) Top Counties',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart for counties lowest number of average days on zillow 
 */
const lowestDaysOnZillowChart = document.getElementById('lowestDaysOnZillowChart');
let avgDaysOnZillow = [];
Object.values(forSaleListings).forEach(listings => {
    listings = listings.filter(data => data.days_on_zillow !== -1)
    if (listings.length >= 3) {
        let averageDays = listings.reduce((a, c) => a + c.days_on_zillow, 0) / listings.length;
        avgDaysOnZillow.push({county: listings[0].county, days: averageDays});
    }
})
avgDaysOnZillow.sort((a, b) => a.days >= b.days ? 1 : -1)
let avgDaysOnZillow15 = avgDaysOnZillow.slice(0, 15);
new Chart(lowestDaysOnZillowChart, {
    type: 'bar',
    data: {
        labels: avgDaysOnZillow15.map(row => row.county),
        datasets: [{
            label: 'Avg Days on Zillow',
            data: avgDaysOnZillow15.map(row => row.days),
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Average days on Zillow (Least)',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart for counties hightst number of average days on zillow 
 */
const highestDaysOnZillowChart = document.getElementById('highestDaysOnZillowChart');
let avgDaysOnZillowMost15 = avgDaysOnZillow.slice(-15).reverse();
new Chart(highestDaysOnZillowChart, {
    type: 'bar',
    data: {
        labels: avgDaysOnZillowMost15.map(row => row.county),
        datasets: [{
            label: 'Avg Days on Zillow',
            data: avgDaysOnZillowMost15.map(row => row.days),
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Average days on Zillow (Most)',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart for highest avg cost per acre
 */
let avgCostPerAcre = [];
Object.values(soldListings).forEach(listings => {
    if (listings.length >= 0) {
        let averageCost = listings.reduce((a, c) => a + (c.price / c.acres), 0) / listings.length;
        avgCostPerAcre.push({county: listings[0].county, costPerAcre: averageCost, listings: listings.length});
    }
})
avgCostPerAcre.sort((a, b) => a.costPerAcre <= b.costPerAcre ? 1 : -1)
let avgCostPerAcre15 = avgCostPerAcre.slice(0, 15);
const highestCostPerAcreChart = document.getElementById('highestCostPerAcreChart');
new Chart(highestCostPerAcreChart, {
    type: 'bar',
    data: {
        labels: avgCostPerAcre15.map(row => row.county),
        datasets: [{
            label: '$ per acre',
            data: avgCostPerAcre15.map(row => row.costPerAcre),
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Highest Avg Cost per Acre (Sold withing 90 days)',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart for lowest avg cost per acre
 */
avgCostPerAcre15 = avgCostPerAcre.reverse().slice(0, 15);
const lowestCostPerAcreChart = document.getElementById('lowestCostPerAcreChart');
new Chart(lowestCostPerAcreChart, {
    type: 'bar',
    data: {
        labels: avgCostPerAcre15.map(row => row.county),
        datasets: [{
            label: '$ Per acre',
            data: avgCostPerAcre15.map(row => row.costPerAcre),
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Lowest Avg Cost per Acre (Sold withing 90 days)',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart for highest median sale price
 */
let medSalePrice = [];
Object.values(soldListings).forEach(listings => {
    if (listings.length >= 3) {
        let medianCost = listings[Math.round(listings.length / 2)].price;
        medSalePrice.push({county: listings[0].county, medSalePrice: medianCost});
    }
})
medSalePrice.sort((a, b) => a.medSalePrice <= b.medSalePrice ? 1 : -1)
let medSalePrice15 = medSalePrice.slice(0, 15);
const highestMedSalePriceChart = document.getElementById('highestMedSalePriceChart');
new Chart(highestMedSalePriceChart, {
    type: 'bar',
    data: {
        labels: medSalePrice15.map(row => row.county),
        datasets: [{
            label: 'Sold Price',
            data: medSalePrice15.map(row => row.medSalePrice),
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Highest Median Sale Price (Sold withing 90 days)',
                font: {
                    size: 25
                }
            }
        }
    }
});

/**
 *  Chart for highest median sale price
 */

medSalePrice15 = medSalePrice.reverse().slice(0, 15);
const lowestMedSalePriceChart = document.getElementById('lowestMedSalePriceChart');
new Chart(lowestMedSalePriceChart, {
    type: 'bar',
    data: {
        labels: medSalePrice15.map(row => row.county),
        datasets: [{
            label: 'Sold Price',
            data: medSalePrice15.map(row => row.medSalePrice),
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Lowest Median Sale Price (Sold withing 90 days)',
                font: {
                    size: 25
                }
            }
        }
    }
});