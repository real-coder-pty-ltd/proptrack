<?php

use RealCoder\Geolocation\PostcodeLookup;
use RealCoder\PropTrack\AddressClient;
use RealCoder\PropTrack\MarketClient;
use RealCoder\PropTrack\PropertiesClient;

function fetchPostcode($suburb, $state, $username)
{
    try {
        $postcodeLookup = new PostcodeLookup($username);
        $postcode = $postcodeLookup->getPostcode($suburb, $state);

        if ($postcode) {
            return $postcode;
        } else {
            echo "Postcode not found for $suburb, $state.";
        }
    } catch (\Exception $e) {
        echo 'Error: '.$e->getMessage();
    }
}

function PropTrackAddressID($query)
{
    try {
        $address = new AddressClient;
        $id = $address->matchAddress($query)['propertyId'];

        if ($id) {
            return $id;
        } else {
            echo "ID not found for $query.";
        }
    } catch (\Exception $e) {
        echo 'Error: '.$e->getMessage();
    }
}

function PropTrackListings($query)
{
    $id = PropTrackAddressID($query);

    try {
        $listings = new PropertiesClient;
        $listings = $listings->getPropertyListings($id);

        if ($listings) {
            return $listings;
        } else {
            echo "Listings not found for $query.";
        }
    } catch (\Exception $e) {
        echo 'Error: '.$e->getMessage();
    }
}

/**
 * Shortcode to display the suburb description.
 */
function PropTrackSuburbDescription(string $suburb, string $state, string $postcode): string|array
{
    // Rent Data
    try {
        $client = new MarketClient;
        $params = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'freqency' => 'monthly',
            'start_date' => (new DateTime('first day of last month'))->format('Y-m-d'),
            'end_date' => (new DateTime('last day of last month'))->format('Y-m-d'),

        ];
        $rent = $client->getSupplyAndDemandData('potential-renters', $params);

    } catch (\Exception $e) {
        error_log('Error fetching supply and demand data: '.$e->getMessage());

        return 'Error fetching supply and demand data: '.$e->getMessage();
    }

    $rent_last_month_supply_house = last(last($rent[0]['dateRanges'])['metricValues'])['supply'];
    $rent_last_month_supply_unit = last(last($rent[1]['dateRanges'])['metricValues'])['supply'];
    $rent_last_month_supply_total = $rent_last_month_supply_house + $rent_last_month_supply_unit;

    // Median Rental Yield
    try {
        $client = new MarketClient;
        $params = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'freqency' => 'monthly',
            'start_date' => (new DateTime('first day of last month'))->format('Y-m-d'),
            'end_date' => (new DateTime('last day of last month'))->format('Y-m-d'),

        ];
        $rentalYield = $client->getHistoricMarketData('rent', 'median-rental-yield', $params);

    } catch (\Exception $e) {
        error_log('Error fetching supply and demand data: '.$e->getMessage());

        return 'Error fetching supply and demand data: '.$e->getMessage();
    }
    $house_median_rental_yield = last($rentalYield[0]['dateRanges'])['metricValues'][0]['value'];
    $unit_median_rental_yield = last($rentalYield[1]['dateRanges'])['metricValues'][0]['value'];

    // Convert rental yield to percentage with 2 decimal places like this: 5.67%
    $house_median_rental_yield = number_format($house_median_rental_yield * 100, 2).'%';
    $unit_median_rental_yield = number_format($unit_median_rental_yield * 100, 2).'%';

    $rentalValue = $client->getHistoricMarketData('rent', 'median-rental-price', $params);

    // Average house rental amount per week
    $rent_house_year = $rentalValue[0]['dateRanges'][0]['metricValues'];
    $rent_house_year = end($rent_house_year)['value'];
    $rent_house_year = '$'.number_format($rent_house_year, 0, '.', ',');

    // Average unit rental amount per week
    $rent_unit_year = $rentalValue[1]['dateRanges'][0]['metricValues'];
    $rent_unit_year = end($rent_unit_year)['value'];
    $rent_unit_year = '$'.number_format($rent_unit_year, 0, '.', ',');

    // Buy Data
    try {
        $client = new MarketClient;
        $params = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'freqency' => 'monthly',
            'start_date' => (new DateTime('first day of last month'))->format('Y-m-d'),
            'end_date' => (new DateTime('last day of last month'))->format('Y-m-d'),

        ];
        $buy = $client->getSupplyAndDemandData('potential-buyers', $params);

    } catch (\Exception $e) {
        error_log('Error fetching supply and demand data: '.$e->getMessage());

        return 'Error fetching supply and demand data: '.$e->getMessage();
    }

    // Buy
    $buy_last_month_supply_house = last(last($buy[0]['dateRanges'])['metricValues'])['supply'];
    $buy_last_month_supply_unit = last(last($buy[1]['dateRanges'])['metricValues'])['supply'];
    $buy_last_month_supply_total = $buy_last_month_supply_house + $buy_last_month_supply_unit;

    // Historic Sale Data
    try {
        $client = new MarketClient;
        $params = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
        ];
        $historicSaleData = $client->getHistoricMarketData('sale', 'median-sale-price', $params);

    } catch (\Exception $e) {
        error_log('Error fetching historic sale data: '.$e->getMessage());

        return 'Error fetching historic sale data: '.$e->getMessage();
    }

    // Median Price
    $medianHousePrice = (int) $historicSaleData[0]['dateRanges'][0]['metricValues'][0]['value'];
    $medianUnitPrice = (int) $historicSaleData[1]['dateRanges'][0]['metricValues'][0]['value'];

    $medianHousePrice = '$'.number_format($medianHousePrice, 0, '.', ',');
    $medianUnitPrice = '$'.number_format($medianUnitPrice, 0, '.', ',');

    // "This Year": last 12 months up to now
    $endDateThisYear = new DateTime; // today
    $startDateThisYear = (clone $endDateThisYear)->modify('-12 months');

    // "Last Year": the 12 months before that
    $endDateLastYear = (clone $startDateThisYear);
    $startDateLastYear = (clone $endDateLastYear)->modify('-12 months');

    try {
        $client = new MarketClient;

        // Params for "This Year" (past 12 months)
        $paramsThisYear = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'startDate' => $startDateThisYear->format('Y-m-d'),
            'endDate' => $endDateThisYear->format('Y-m-d'),
        ];

        // Params for "Last Year" (the 12 months prior to that)
        $paramsLastYear = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'startDate' => $startDateLastYear->format('Y-m-d'),
            'endDate' => $endDateLastYear->format('Y-m-d'),
        ];

        // Fetch data for both periods
        $historicSaleDataThisYear = $client->getHistoricMarketData('sale', 'median-sale-price', $paramsThisYear);
        $historicSaleDataLastYear = $client->getHistoricMarketData('sale', 'median-sale-price', $paramsLastYear);

    } catch (\Exception $e) {
        error_log('Error fetching historic sale data: '.$e->getMessage());

        return 'Error fetching historic sale data: '.$e->getMessage();
    }

    $sales_house_average_this_year = $historicSaleDataThisYear[0]['dateRanges'][0]['metricValues'];
    $sales_house_average_this_year = end($sales_house_average_this_year)['value'];

    $sales_house_average_last_year = $historicSaleDataLastYear[0]['dateRanges'][0]['metricValues'];
    $sales_house_average_last_year = end($sales_house_average_last_year)['value'];

    $growthRate = ($sales_house_average_this_year - $sales_house_average_last_year) / $sales_house_average_last_year * 100;

    $growthRate = number_format($growthRate, 2, '.', '').'%';

    $sales_unit_average_this_year = $historicSaleDataThisYear[1]['dateRanges'][0]['metricValues'];
    $sales_unit_average_this_year = end($sales_unit_average_this_year)['value'];

    $sales_unit_average_last_year = $historicSaleDataLastYear[1]['dateRanges'][0]['metricValues'];
    $sales_unit_average_last_year = end($sales_unit_average_last_year)['value'];

    $unitGrowthRate = ($sales_unit_average_this_year - $sales_unit_average_last_year) / $sales_unit_average_last_year * 100;

    $unitGrowthRate = number_format($unitGrowthRate, 2, '.', '').'%';

    $text = sprintf('Last month <strong>%s</strong> had <strong>%d</strong> properties available for rent and <strong>%d</strong> properties for sale. '.
        'Median property prices over the last year range from <strong>%s</strong> for houses to <strong>%s</strong> for units. '.
        'If you are looking for an investment property, consider houses in <strong>%s</strong> rent out for <strong>%s</strong> with an annual rental yield of <strong>%s</strong> '.
        'and units rent for <strong>%s</strong> with a rental yield of <strong>%s</strong>. <strong>%s</strong> has seen an annual compound growth rate of <strong>%s</strong> for houses and <strong>%s</strong> for units.',
        $suburb,
        $rent_last_month_supply_total,
        $buy_last_month_supply_total,
        $medianHousePrice,
        $medianUnitPrice,
        $suburb,
        $rent_house_year,
        $house_median_rental_yield,
        $rent_unit_year,
        $unit_median_rental_yield,
        $suburb,
        $growthRate,
        $unitGrowthRate
    );

    return $text;
}

function PropTrackMonthlySnapshots(string $suburb, string $state, $postcode, string $type = 'sale' , string $metric = 'median-sale-price' ): array
{

    $client = new MarketClient;

    // Prepare a structure for each bedroom category
    $bedroomData = [
        '1' => ['labels' => [], 'values' => []],
        '2' => ['labels' => [], 'values' => []],
        '3' => ['labels' => [], 'values' => []],
        '4' => ['labels' => [], 'values' => []],
        '5+' => ['labels' => [], 'values' => []],
        'combined' => ['labels' => [], 'values' => []],
    ];

    // Anchor: last day of previous month
    // Example: if today = Jan 20, 2025 => anchor = Dec 31, 2024
    $currentEnd = new DateTime('last day of previous month');

    // We'll do 4 calls, each capturing a distinct previous year
    // We'll store them in ascending order, so we need an array for the partial results
    $allBlocks = [];

    // The 1-year block ends on $currentEnd
    // The block start is exactly 1 year earlier (+1 day so it’s inclusive)
    $blockStart = (clone $currentEnd)->modify('-1 year +1 day');

    // Prepare parameters
    $params = [
        'suburb' => $suburb,
        'postcode' => $postcode,
        'state' => $state,
        'propertyTypes' => ['house'],
        'frequency' => 'monthly',
        'start_date' => $blockStart->format('Y-m-d'),
        'end_date' => $currentEnd->format('Y-m-d'),
    ];

    try {
        // Fetch 12 "rolling monthly" dateRanges from the API
        $yearData = $client->getHistoricMarketData($type, $metric, $params);
        // Store the raw year block so we can merge it after the loop
        $allBlocks[] = $yearData;

        // error_log(print_r($yearData, true));
    } catch (\Exception $e) {
        error_log('Error fetching monthly sale data: '.$e->getMessage());
    }

    // Move currentEnd back 1 year for the next iteration
    // e.g., from 2024-12-31 => 2023-12-31
    $currentEnd = (clone $blockStart)->modify('-1 day');
    // That means the next block will be the year prior to this block

    // Now we have 4 blocks in chronological DESC order (the last iteration is the oldest year).
    // If you prefer ascending, we can reverse them:
    $allBlocks = array_reverse($allBlocks);

    // Parse the blocks from oldest to newest
    foreach ($allBlocks as $yearBlock) {
        // Each block is an array of property types, e.g. [ [ 'propertyType' => 'house', 'dateRanges' => [...]] ]
        foreach ($yearBlock as $item) {
            $dateRanges = $item['dateRanges'] ?? [];
            foreach ($dateRanges as $range) {
                // Each range => 'startDate' => ..., 'endDate' => ..., 'metricValues' => [...]
                $endDate = $range['endDate'];
                $metrics = $range['metricValues'] ?? [];

                foreach ($metrics as $metric) {
                    $bedrooms = $metric['bedrooms'];
                    $value = $metric['value'];

                    if (isset($bedroomData[$bedrooms])) {
                        $bedroomData[$bedrooms]['labels'][] = $endDate;
                        $bedroomData[$bedrooms]['values'][] = $value;
                    }
                }
            }
        }
    }

    return $bedroomData;
}

function PropTrackSupplyandDemandData(string $suburb, string $state, $postcode, string $property_type, string $metric): array
{
    $client = new MarketClient;

    // Prepare a structure for each bedroom category
    $bedroomData = [
        '1' => ['labels' => [], 'values' => []],
        '2' => ['labels' => [], 'values' => []],
        '3' => ['labels' => [], 'values' => []],
        '4' => ['labels' => [], 'values' => []],
        '5+' => ['labels' => [], 'values' => []],
        'combined' => ['labels' => [], 'values' => []],
    ];

    // Anchor: last day of previous month
    // Example: if today = Jan 20, 2025 => anchor = Dec 31, 2024
    $currentEnd = new DateTime('last day of previous month');

    // We'll do 4 calls, each capturing a distinct previous year
    // We'll store them in ascending order, so we need an array for the partial results
    $allBlocks = [];

    // for ($i = 0; $i < 4; $i++) {
    // The 1-year block ends on $currentEnd
    // The block start is exactly 1 year earlier (+1 day so it’s inclusive)
    $blockStart = (clone $currentEnd)->modify('-1 year +1 day');

    // Prepare parameters
    $params = [
        'suburb' => $suburb,
        'postcode' => $postcode,
        'state' => $state,
        'propertyTypes' => ['house', 'unit'],
        'frequency' => 'monthly',
        'start_date' => $blockStart->format('Y-m-d'),
        'end_date' => $currentEnd->format('Y-m-d'),
    ];
    // error_log(print_r($params, true));

    // error_log(sprintf(
    //     "API Call #%d => %s to %s",
    //     $i+1,
    //     $params['start_date'],
    //     $params['end_date']
    // ));

    try {
        // Fetch 12 "rolling monthly" dateRanges from the API
        $yearData = $client->getSupplyAndDemandData($metric, $params);

        // Store the raw year block so we can merge it after the loop
        $allBlocks[] = $yearData;

        // error_log(print_r($yearData, true));
    } catch (\Exception $e) {
        error_log('Error fetching monthly sale data: '.$e->getMessage());
    }

    // Move currentEnd back 1 year for the next iteration
    // e.g., from 2024-12-31 => 2023-12-31
    $currentEnd = (clone $blockStart)->modify('-1 day');
    // That means the next block will be the year prior to this block
    // }

    // Now we have 4 blocks in chronological DESC order (the last iteration is the oldest year).
    // If you prefer ascending, we can reverse them:
    $allBlocks = array_reverse($allBlocks);

    // Initialize organized data array
    $organizedData = [];

    // Process data
    foreach ($allBlocks[0] as $property) {
        $propertyType = $property['propertyType'];

        foreach ($property['dateRanges'] as $dateRange) {
            $startDate = $dateRange['startDate'];
            $endDate = $dateRange['endDate'];

            foreach ($dateRange['metricValues'] as $metric) {
                $bedrooms = $metric['bedrooms'];

                // Organize data
                $organizedData[$propertyType][$bedrooms][] = [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'supply' => $metric['supply'] ?? 0,
                    'demand' => $metric['demand'] ?? 0,
                ];
            }
        }
    }

    return $organizedData;
}

function PropTrackMedianPriceInsights(string $suburb, string $state, string $postcode): array
{
    $client = new MarketClient;

    // Prepare a structure for each bedroom category
    $bedroomData = [
        '1' => ['labels' => [], 'values' => []],
        '2' => ['labels' => [], 'values' => []],
        '3' => ['labels' => [], 'values' => []],
        '4' => ['labels' => [], 'values' => []],
        '5+' => ['labels' => [], 'values' => []],
        'combined' => ['labels' => [], 'values' => []],
    ];

    // Anchor: last day of previous month
    // Example: if today = Jan 20, 2025 => anchor = Dec 31, 2024
    $currentEnd = new DateTime('last day of previous month');

    // We'll do 4 calls, each capturing a distinct previous year
    // We'll store them in ascending order, so we need an array for the partial results
    $allBlocks = [];

    // for ($i = 0; $i < 4; $i++) {
    // The 1-year block ends on $currentEnd
    // The block start is exactly 1 year earlier (+1 day so it’s inclusive)
    $blockStart = (clone $currentEnd)->modify('-1 year +1 day');

    // Prepare parameters
    $params = [
        'suburb' => $suburb,
        'postcode' => $postcode,
        'state' => $state,
        'propertyTypes' => ['house', 'unit'],
        'frequency' => 'yearly',
        'start_date' => $blockStart->format('Y-m-d'),
        'end_date' => $currentEnd->format('Y-m-d'),
    ];

    function reorganizeData($data, $key)
    {
        $result = [];
        foreach ($data as $propertyTypes) {
            // Sort dateRanges by endDate in descending order
            usort($propertyTypes['dateRanges'], function ($a, $b) {
                return strtotime($b['endDate']) - strtotime($a['endDate']);
            });

            foreach ($propertyTypes['dateRanges'] as $dateRange) {
                foreach ($dateRange['metricValues'] as $metricValue) {
                    $bedrooms = $metricValue['bedrooms'];
                    $value = $metricValue['value'];
                    $result[$bedrooms][$propertyTypes['propertyType']][$key] = $value;
                }
                break; // Only get the first date range for each bedroom
            }
        }

        return $result;
    }

    try {
        // Fetch 12 "rolling monthly" dateRanges from the API
        $median_sale_price = reorganizeData($client->getHistoricMarketData('sale', 'median-sale-price', $params), 'median_sale_price');
        $median_sale_days_on_market = reorganizeData($client->getHistoricMarketData('sale', 'median-days-on-market', $params), 'median_sale_days_on_market');
        $median_rental_price = reorganizeData($client->getHistoricMarketData('rent', 'median-rental-price', $params), 'median_rental_price');
        $median_rental_days_on_market = reorganizeData($client->getHistoricMarketData('rent', 'median-days-on-market', $params), 'median_rental_days_on_market');

        // Merge the data
        $merged_data = [];
        foreach ([$median_sale_price, $median_sale_days_on_market, $median_rental_price, $median_rental_days_on_market] as $data) {
            foreach ($data as $bedrooms => $propertyTypes) {
                foreach ($propertyTypes as $propertyType => $values) {
                    foreach ($values as $key => $value) {
                        $merged_data[$bedrooms][$propertyType][$key] = $value;
                    }
                }
            }
        }

        // Sort the bedrooms by ascending order
        ksort($merged_data);

        // Ensure 'house' comes before 'unit' for each bedroom
        foreach ($merged_data as $bedrooms => &$propertyTypes) {
            if (isset($propertyTypes['house']) && isset($propertyTypes['unit'])) {
                $propertyTypes = array_merge(['house' => $propertyTypes['house']], ['unit' => $propertyTypes['unit']]);
            }
        }

        // Store the raw year block so we can merge it after the loop
        $allBlocks[] = $merged_data;

        // error_log(print_r($yearData, true));
    } catch (\Exception $e) {
        error_log('Error fetching monthly sale data: '.$e->getMessage());
    }

    $currentEnd = (clone $blockStart)->modify('-1 day');

    // dd($merged_data);

    return $merged_data;
}

function PropTrackGetNearbySchools(string $suburb)
{
    $data = new RealCoder\LocalSchools($suburb);

    return $data->localSchools;
}
