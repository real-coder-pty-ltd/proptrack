<?php

use RealCoder\Geolocation\PostcodeLookup;
use RealCoder\PropTrack\AddressClient;
use RealCoder\PropTrack\MarketClient;
use RealCoder\PropTrack\PropertiesClient;
use RealCoder\PropTrack\ReportsClient;

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
        echo 'Error: ' . $e->getMessage();
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
        echo 'Error: ' . $e->getMessage();
    }
}

function PropTrackAddressSuggest($query)
{
    try {
        $address = new AddressClient;
        $suggest = $address->getAddressSuggestions($query);

        if ($suggest) {
            return $suggest;
        } else {
            echo "Address not found for $query.";
        }
    } catch (\Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

function PropTrackPropertySummary($id)
{
    try {
        $property = new PropertiesClient;
        $summary = $property->getPropertySummary($id);

        if ($summary) {
            return $summary;
        } else {
            echo "Summary not found for $id.";
        }
    } catch (\Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

function PropTrackPropertyReport($id)
{
    try {
        $report = new ReportsClient;
        $property_report = $report->getReport($id);

        if ($property_report) {
            return $property_report;
        } else {
            // echo "Report not found for $id.";
            return null;
        }
    } catch (\Exception $e) {
        // echo 'Error: ' . $e->getMessage();
        return null;
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
        echo 'Error: ' . $e->getMessage();
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
        error_log('Error fetching supply and demand data: ' . $e->getMessage());

        return 'Error fetching supply and demand data: ' . $e->getMessage();
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
        error_log('Error fetching supply and demand data: ' . $e->getMessage());

        return 'Error fetching supply and demand data: ' . $e->getMessage();
    }
    $house_median_rental_yield = last($rentalYield[0]['dateRanges'])['metricValues'][0]['value'];
    $unit_median_rental_yield = last($rentalYield[1]['dateRanges'])['metricValues'][0]['value'];

    // Convert rental yield to percentage with 2 decimal places like this: 5.67%
    $house_median_rental_yield = number_format($house_median_rental_yield * 100, 2) . '%';
    $unit_median_rental_yield = number_format($unit_median_rental_yield * 100, 2) . '%';

    $rentalValue = $client->getHistoricMarketData('rent', 'median-rental-price', $params);

    // Average house rental amount per week
    $rent_house_year = $rentalValue[0]['dateRanges'][0]['metricValues'] ?? [];
    $rent_house_year = end($rent_house_year)['value'] ?? 0;
    $rent_house_year = '$' . number_format($rent_house_year, 0, '.', ',');

    // Average unit rental amount per week
    $rent_unit_year = $rentalValue[1]['dateRanges'][0]['metricValues'] ?? [];
    if (isset($rent_unit_year) && is_array($rent_unit_year)) {
        $rent_unit_year = end($rent_unit_year)['value'] ?? 0;
        $rent_unit_year = '$' . number_format($rent_unit_year, 0, '.', ',');
    } else {
        $rent_unit_year = '';
    }

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
        error_log('Error fetching supply and demand data: ' . $e->getMessage());

        return 'Error fetching supply and demand data: ' . $e->getMessage();
    }

    // Buy
    $buy_last_month_supply_house = isset($buy[0]['dateRanges']) ? last(last($buy[0]['dateRanges'])['metricValues'])['supply'] ?? 0 : 0;
    $buy_last_month_supply_unit = isset($buy[1]['dateRanges']) ? last(last($buy[1]['dateRanges'])['metricValues'])['supply'] ?? 0 : 0;
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
        error_log('Error fetching historic sale data: ' . $e->getMessage());

        return 'Error fetching historic sale data: ' . $e->getMessage();
    }

    // Median Price
    $medianHousePrice = isset($historicSaleData[0]['dateRanges'][0]['metricValues'][0]['value']) ? (int) $historicSaleData[0]['dateRanges'][0]['metricValues'][0]['value'] : 0;
    $medianUnitPrice = isset($historicSaleData[1]['dateRanges'][0]['metricValues'][0]['value']) ? (int) $historicSaleData[1]['dateRanges'][0]['metricValues'][0]['value'] : 0;

    $medianHousePrice = '$' . number_format($medianHousePrice, 0, '.', ',');
    $medianUnitPrice = '$' . number_format($medianUnitPrice, 0, '.', ',');

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
        error_log('Error fetching historic sale data: ' . $e->getMessage());

        return 'Error fetching historic sale data: ' . $e->getMessage();
    }

    $sales_house_average_this_year = isset($historicSaleDataThisYear[0]['dateRanges'][0]['metricValues']) ? $historicSaleDataThisYear[0]['dateRanges'][0]['metricValues'] : [];
    $sales_house_average_this_year_value = end($sales_house_average_this_year);
    $sales_house_average_this_year = $sales_house_average_this_year_value !== false ? $sales_house_average_this_year_value['value'] : 0;

    $sales_house_average_last_year = isset($historicSaleDataLastYear[0]['dateRanges'][0]['metricValues']) ? $historicSaleDataLastYear[0]['dateRanges'][0]['metricValues'] : [];
    $sales_house_average_last_year_value = end($sales_house_average_last_year);
    $sales_house_average_last_year = $sales_house_average_last_year_value !== false ? $sales_house_average_last_year_value['value'] : 0;

    $growthRate = 0;
    if ($sales_house_average_last_year != 0) {
        $growthRate = ($sales_house_average_this_year - $sales_house_average_last_year) / $sales_house_average_last_year * 100;
    }
    $growthRate = number_format($growthRate, 2, '.', '') . '%';

    $sales_unit_average_this_year = $historicSaleDataThisYear[1]['dateRanges'][0]['metricValues'] ?? [];
    $sales_unit_average_this_year = end($sales_unit_average_this_year)['value'] ?? 0;

    $sales_unit_average_last_year = $historicSaleDataLastYear[1]['dateRanges'][0]['metricValues'] ?? [];
    $sales_unit_average_last_year = end($sales_unit_average_last_year)['value'] ?? 0;

    $unitGrowthRate = 0;
    if ($sales_unit_average_last_year != 0) {
        $unitGrowthRate = ($sales_unit_average_this_year - $sales_unit_average_last_year) / $sales_unit_average_last_year * 100;
    }

    $unitGrowthRate = number_format($unitGrowthRate, 2, '.', '') . '%';

    $text = sprintf('Last month <strong>%s</strong> had <strong>%d</strong> properties available for rent and <strong>%d</strong> properties for sale. ' .
        'Median property prices over the last year range from <strong>%s</strong> for houses to <strong>%s</strong> for units. ' .
        'If you are looking for an investment property, consider houses in <strong>%s</strong> rent out for <strong>%s</strong> with an annual rental yield of <strong>%s</strong> ' .
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

function PropTrackMonthlySnapshots(string $suburb, string $state, $postcode, string $type = 'sale', string $metric = 'median-sale-price'): array
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
        error_log('Error fetching monthly sale data: ' . $e->getMessage());
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
        error_log('Error fetching monthly sale data: ' . $e->getMessage());
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
    if (! empty($allBlocks) && isset($allBlocks[0])) {
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
    } else {
        // Handle the case where $allBlocks is empty or does not contain the expected structure
        // For example, you can initialize $organizedData as an empty array
        $organizedData = [];
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

    // Merge the data
    $merged_data = [];

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
        error_log('Error fetching monthly sale data: ' . $e->getMessage());
    }

    $currentEnd = (clone $blockStart)->modify('-1 day');

    return $merged_data;
}

function PropTrackMarketMetrics(string $suburb, string $state, string $postcode): array
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
    $currentEnd = new DateTime('last day of previous month');

    // The block start is the first day of the month before the last month
    $blockStart = (clone $currentEnd)->modify('-2 months +1 day');

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

    // Merge the data
    $merged_data = [];

    function reorganizeData2($data, $key)
    {
        $result = [];
        foreach ($data as $propertyTypes) {
            // Sort dateRanges by endDate in descending order
            usort($propertyTypes['dateRanges'], function ($a, $b) {
                return strtotime($b['endDate']) - strtotime($a['endDate']);
            });

            $firstIndex = 0;
            $lastIndex = count($propertyTypes['dateRanges']) - 1;

            foreach ($propertyTypes['dateRanges'] as $index => $dateRange) {
                foreach ($dateRange['metricValues'] as $metricValue) {
                    $bedrooms = $metricValue['bedrooms'];
                    $value = $metricValue['value'];
                    if ($index == $firstIndex) {
                        $result[$bedrooms][$propertyTypes['propertyType']][$key] = $value;
                    } elseif ($index == $lastIndex) {
                        $result[$bedrooms][$propertyTypes['propertyType']][$key . '_previous'] = $value;
                    }
                }
                if ($index > $lastIndex) {
                    break;
                }
            }
        }

        return $result;
    }

    function calculateGrowthRate($current, $previous)
    {
        if ($current == 0) {
            return null; // Avoid division by zero
        }

        return (($current - $previous) / $previous) * 100;
    }

    try {
        // Fetch 12 "rolling monthly" dateRanges from the API
        $median_sale_price = reorganizeData2($client->getHistoricMarketData('sale', 'median-sale-price', $params), 'median_sale_price');
        $median_sale_transaction_volume = reorganizeData2($client->getHistoricMarketData('sale', 'sale-transaction-volume', $params), 'median_sale_transaction_volume');
        $median_rental_price = reorganizeData2($client->getHistoricMarketData('rent', 'median-rental-price', $params), 'median_rental_price');
        $median_rental_transaction_volume = reorganizeData2($client->getHistoricMarketData('rent', 'rental-transaction-volume', $params), 'median_rental_transaction_volume');
        $median_rental_yield = reorganizeData2($client->getHistoricMarketData('rent', 'median-rental-yield', $params), 'median_rental_yield');

        foreach ([$median_sale_price, $median_sale_transaction_volume, $median_rental_price, $median_rental_transaction_volume, $median_rental_yield] as $data) {
            foreach ($data as $bedrooms => $propertyTypes) {
                foreach ($propertyTypes as $propertyType => $values) {
                    foreach ($values as $key => $value) {
                        $merged_data[$bedrooms][$propertyType][$key] = $value;
                    }
                }
            }
        }

        // Calculate growth rate
        foreach ($merged_data as $bedrooms => &$propertyTypes) {
            foreach ($propertyTypes as $propertyType => &$values) {
                // Calculate growth rate for median_sale_price
                if (isset($values['median_sale_price']) && isset($values['median_sale_price_previous'])) {
                    $currentSalePrice = $values['median_sale_price'];
                    $previousSalePrice = $values['median_sale_price_previous'];
                    $values['median_sale_price_growth_rate'] = calculateGrowthRate($currentSalePrice, $previousSalePrice);
                }

                // Calculate growth rate for median_rental_price
                if (isset($values['median_rental_price']) && isset($values['median_rental_price_previous'])) {
                    $currentRentalPrice = $values['median_rental_price'];
                    $previousRentalPrice = $values['median_rental_price_previous'];
                    $values['median_rental_price_growth_rate'] = calculateGrowthRate($currentRentalPrice, $previousRentalPrice);
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

        // Include current start and end date
        $merged_data['start_date'] = $blockStart->format('Y-m-d');
        $merged_data['end_date'] = $currentEnd->format('Y-m-d');

    } catch (\Exception $e) {
        error_log('Error fetching monthly sale data: ' . $e->getMessage());
    }

    return $merged_data;
}

function PropTrackGetNearbySchools(string $suburb)
{
    $data = new RealCoder\LocalSchools($suburb);

    return $data;
}

function PropTrackCalculateDrivingDistance($startLat, $startLon, $endLat, $endLon)
{
    $url = "http://router.project-osrm.org/route/v1/driving/{$startLon},{$startLat};{$endLon},{$endLat}?overview=false";

    $response = file_get_contents($url);
    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);
    if (isset($data['routes'][0]['distance'])) {
        return $data['routes'][0]['distance'] / 1000;
    }

    return false;
}

function PropTrackSurroundingSuburbs(string $suburb, string $state, string $postcode): array
{
    $url = 'https://nominatim.openstreetmap.org/search?city=' . urlencode($suburb) . '&state=' . urlencode($state) . '&postalcode=' . urlencode($postcode) . '&format=json&addressdetails=1';

    $opts = [
        'http' => [
            'header' => "User-Agent: MyApp/1.0\r\n",
        ],
    ];

    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (empty($data)) {
        return [];
    }

    $lat = $data[0]['lat'];
    $lon = $data[0]['lon'];

    $overpassUrl = 'https://overpass-api.de/api/interpreter';
    $query = '[out:json];(node["place"="suburb"](around:5000,' . $lat . ',' . $lon . '););out body;';

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: MyApp/1.0\r\n",
            'content' => http_build_query(['data' => $query]),
        ],
    ];

    $context = stream_context_create($opts);
    $response = file_get_contents($overpassUrl, false, $context);
    $data = json_decode($response, true);

    $surroundingSuburbs = [];

    if (isset($data['elements'])) {
        foreach ($data['elements'] as $element) {
            if (isset($element['tags']['name'])) {
                $surroundingSuburbs[] = $element['tags']['name'];
            }
        }
    }

    return $surroundingSuburbs;
}

// Helper function to get full state name from input
function proptrack_get_full_state_name($input)
{
    $states = [
        'NSW' => 'New South Wales',
        'QLD' => 'Queensland',
        'SA' => 'South Australia',
        'TAS' => 'Tasmania',
        'VIC' => 'Victoria',
        'WA' => 'Western Australia',
        'ACT' => 'Australian Capital Territory',
        'NT' => 'Northern Territory',
        'New South Wales' => 'New South Wales',
        'Queensland' => 'Queensland',
        'South Australia' => 'South Australia',
        'Tasmania' => 'Tasmania',
        'Victoria' => 'Victoria',
        'Western Australia' => 'Western Australia',
        'Australian Capital Territory' => 'Australian Capital Territory',
        'Northern Territory' => 'Northern Territory',
    ];

    $input_upper = strtoupper($input);
    $input_ucwords = ucwords(strtolower($input));

    if (isset($states[$input_upper])) {
        return $states[$input_upper];
    } elseif (isset($states[$input_ucwords])) {
        return $states[$input_ucwords];
    }

    return null;
}

// Helper function to get state abbreviation from input
function proptrack_get_state_abbreviation($input)
{
    $states = [
        'NSW' => 'NSW',
        'QLD' => 'QLD',
        'SA' => 'SA',
        'TAS' => 'TAS',
        'VIC' => 'VIC',
        'WA' => 'WA',
        'ACT' => 'ACT',
        'NT' => 'NT',
        'New South Wales' => 'NSW',
        'Queensland' => 'QLD',
        'South Australia' => 'SA',
        'Tasmania' => 'TAS',
        'Victoria' => 'VIC',
        'Western Australia' => 'WA',
        'Australian Capital Territory' => 'ACT',
        'Northern Territory' => 'NT',
    ];

    $input_upper = strtoupper($input);
    $input_ucwords = ucwords(strtolower($input));

    if (isset($states[$input_upper])) {
        return $states[$input_upper];
    } elseif (isset($states[$input_ucwords])) {
        return $states[$input_ucwords];
    }

    return null;
}
