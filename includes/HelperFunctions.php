<?php

use RealCoder\Geolocation\PostcodeLookup;
use RealCoder\PropTrack\MarketClient;

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

/**
 * Shortcode to display the suburb description.
 */
function PropTrackSuburbDescription(string $suburb, string $state, $username): string|array
{
    $postcode = fetchPostcode($suburb, $state, $username);

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

    // dd($rentalValue);
    // Average house rental amount per week
    $rent_house_year = $rentalValue[0]['dateRanges'][0]['metricValues'];
    $rent_house_year = end($rent_house_year)['value'];
    $rent_house_year = '$' . number_format($rent_house_year, 0, '.', ',');

    // Average unit rental amount per week
    $rent_unit_year = $rentalValue[1]['dateRanges'][0]['metricValues'];
    $rent_unit_year = end($rent_unit_year)['value'];
    $rent_unit_year = '$' . number_format($rent_unit_year, 0, '.', ',');

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

    $medianHousePrice = '$' . number_format($medianHousePrice, 0, '.', ',');
    $medianUnitPrice = '$' . number_format($medianUnitPrice, 0, '.', ',');

    // "This Year": last 12 months up to now
    $endDateThisYear   = new DateTime(); // today
    $startDateThisYear = (clone $endDateThisYear)->modify('-12 months');

    // "Last Year": the 12 months before that
    $endDateLastYear   = (clone $startDateThisYear);
    $startDateLastYear = (clone $endDateLastYear)->modify('-12 months');

    try {
        $client = new MarketClient();
        
        // Params for "This Year" (past 12 months)
        $paramsThisYear = [
            'suburb'        => $suburb,
            'state'         => $state,
            'postcode'      => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'startDate'     => $startDateThisYear->format('Y-m-d'),
            'endDate'       => $endDateThisYear->format('Y-m-d'),
        ];
        
        // Params for "Last Year" (the 12 months prior to that)
        $paramsLastYear = [
            'suburb'        => $suburb,
            'state'         => $state,
            'postcode'      => $postcode,
            'propertyTypes' => ['house', 'unit'],
            'startDate'     => $startDateLastYear->format('Y-m-d'),
            'endDate'       => $endDateLastYear->format('Y-m-d'),
        ];
    
        // Fetch data for both periods
        $historicSaleDataThisYear = $client->getHistoricMarketData('sale', 'median-sale-price', $paramsThisYear);
        $historicSaleDataLastYear = $client->getHistoricMarketData('sale', 'median-sale-price', $paramsLastYear);
    
    } catch (\Exception $e) {
        error_log('Error fetching historic sale data: ' . $e->getMessage());
        return 'Error fetching historic sale data: ' . $e->getMessage();
    }

    $sales_house_average_this_year = $historicSaleDataThisYear[0]['dateRanges'][0]['metricValues'];
    $sales_house_average_this_year = end($sales_house_average_this_year)['value'];

    $sales_house_average_last_year = $historicSaleDataLastYear[0]['dateRanges'][0]['metricValues'];
    $sales_house_average_last_year = end($sales_house_average_last_year)['value'];

    $growthRate = ($sales_house_average_this_year - $sales_house_average_last_year) / $sales_house_average_last_year * 100; 

    $growthRate = number_format($growthRate, 2, '.', '',) . '%';

    $sales_unit_average_this_year = $historicSaleDataThisYear[1]['dateRanges'][0]['metricValues'];
    $sales_unit_average_this_year = end($sales_unit_average_this_year)['value'];

    $sales_unit_average_last_year = $historicSaleDataLastYear[1]['dateRanges'][0]['metricValues'];
    $sales_unit_average_last_year = end($sales_unit_average_last_year)['value'];

    $unitGrowthRate = ($sales_unit_average_this_year - $sales_unit_average_last_year) / $sales_unit_average_last_year * 100;

    $unitGrowthRate = number_format($unitGrowthRate, 2, '.', '',) . '%';

    $text = sprintf('Last month <strong>%s</strong> had <strong>%d</strong> properties available for rent and <strong>%d</strong> properties for sale. '.
        'Median property prices over the last year range from <strong>%s</strong> for houses to <strong>%s</strong> for units. '.
        "If you are looking for an investment property, consider houses in <strong>%s</strong> rent out for <strong>%s</strong> with an annual rental yield of <strong>%s</strong> " .
        "and units rent for <strong>%s</strong> with a rental yield of <strong>%s</strong>. <strong>%s</strong> has seen an annual compound growth rate of <strong>%s</strong> for houses and <strong>%s</strong> for units.",
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

function PropTrackMarketInsights($suburb, $state, $username): array
{
    $postcode = fetchPostcode($suburb, $state, $username);
    $client = new MarketClient();

    $historicSaleData = [];
    $endOfLastMonth = new DateTime('last day of previous month');

    // 2) We want four 1-year segments:
    //    - Segment 1: 4 years ago -> 3 years ago
    //    - Segment 2: 3 years ago -> 2 years ago
    //    - Segment 3: 2 years ago -> 1 year ago
    //    - Segment 4: 1 year ago -> "last day of previous month"
    // 
    // We'll loop from i=3 down to i=0 so that 
    //   i=3 => covers the oldest block, 
    //   i=0 => covers the newest block.
    for ($i = 3; $i >= 0; $i--) {

        // The end date for this block is "X years before endOfLastMonth".
        // Example: If i=3 and endOfLastMonth is 2024-12-31, blockEnd = 2021-12-31
        $blockEnd = (clone $endOfLastMonth)->modify("-{$i} years");

        // The start date is exactly 1 year earlier (minus 1 year), plus 1 day
        // so we don't overlap days between blocks.
        // Example: If blockEnd is 2021-12-31, 
        //          blockStart becomes 2020-01-01
        $blockStart = (clone $blockEnd)->modify('-1 year +1 day');

        // Build API params for this 1-year segment.
        $params = [
            'suburb'        => $suburb,
            'postcode'      => $postcode,
            'state'         => $state,
            'propertyTypes' => ['house'],
            'frequency'     => 'monthly',
            'start_date'    => $blockStart->format('Y-m-d'),
            'end_date'      => $blockEnd->format('Y-m-d'),
        ];

        error_log("Fetching data from {$params['start_date']} to {$params['end_date']}");

        try {
            // Each call should fetch up to 12 months of data (the API’s max 1-year limit).
            $yearData = $client->getHistoricMarketData('sale', 'median-sale-price', $params);

            // Merge these 12 months of data into our overall array.
            // Adjust merging logic if the API returns objects or differently shaped data.
            $historicSaleData = array_merge($historicSaleData, $yearData);

        } catch (\Exception $e) {
            error_log('Error fetching historic sale data for this 1-year block: ' . $e->getMessage());
        }
    }

    // After looping, $historicSaleData should contain 4 years of monthly data.
    return $historicSaleData;
}
