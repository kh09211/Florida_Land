/**
 *  Charts for use with the Zillow stats for Florida land project
 */

let orderedSold = soldTotals.slice();
orderedSold.sort((a,b) => a.total <= b.total ? 1 : -1);

let orderedForSale = forSaleTotals.slice();
orderedForSale.sort((a,b) => a.total <= b.total ? 1 : -1);

let groupedTotalsByCounty = {};
soldTotals.forEach(data => {
    console.log(data.county)
    groupedTotalsByCounty[data.county] = {sold: data};
});
forSaleTotals.forEach(data => {
    groupedTotalsByCounty[data.county].forSale = data;
});

const allForSaleChart = document.getElementById('allForSaleChart');
new Chart(allForSaleChart, {
    type: 'bar',
    data: {
        labels: Object.keys(groupedTotalsByCounty),
        datasets: [
        {
            label: '# of Lots Sold',
            data: Object.values(groupedTotalsByCounty).map(data => data.sold.total),
            borderWidth: 1,
            barThickness: 15,
            backgroundColor: "dodgerblue"
        },
        {
            label: '# of Lots For Sale',
            data: Object.values(groupedTotalsByCounty).map(data => data.forSale.total),
            borderWidth: 1,
            barThickness: 15,
            backgroundColor: "gold",
        }
        ]
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
            },
            x: {
                max: 2000
            }
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

//console.log(orderedForSale.reduce((accumulator, currentValue) => accumulator + currentValue.total, 0));
const mostForSaleChart = document.getElementById('mostForSaleChart');
let mostForSalePie = orderedForSale.slice(0, 6);
mostForSalePie.push({county: 'All other counties', total: orderedForSale.slice(6).reduce((accumulator, currentValue) => accumulator + currentValue.total, 0)})
new Chart(mostForSaleChart, {
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
                text: 'Lots For Sale (90 days) Top Counties',
                font: {
                    size: 25
                }
            }
        }
    }
});

const lowestDaysOnZillowChart = document.getElementById('lowestDaysOnZillowChart');
let avgDaysOnZillow = [];
Object.values(forSaleListings).forEach(listings => {
    if (listings.length >= 10) {
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


const leastSoldChart = document.getElementById('leastSoldChart');
new Chart(leastSoldChart, {
    type: 'bar',
    data: {
        labels: orderedSold.map(row => row.county).slice(-15),
        datasets: [{
            label: '# of Lots',
            data: orderedSold.map(row => row.total).slice(-15),
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
                text: 'Sold (90 days) Lowest 15 counties',
                font: {
                    size: 25
                }
            }
        }
    }
});

/*
const avgTimeOnMarketChart = document.getElementById('avgTimeOnMarketChart');
new Chart(avgTimeOnMarketChart, {
    type: 'bar',
    data: {
        labels: orderedSold.map(row => row.county).slice(-15),
        datasets: [{
            label: 'Average time on market (Listings withing 90 days)',
            data: orderedSold.map(row => row.total).slice(-15),
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
                text: 'Number of SS vs Number of Trucks',
                font: {
                    size: 25
                }
            }
        }
    }
});
*/