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
    }

    // Buy 
    $buy_last_month_supply_house = last(last($buy[0]['dateRanges'])['metricValues'])['supply'];
    $buy_last_month_supply_unit = last(last($buy[1]['dateRanges'])['metricValues'])['supply'];
    $buy_last_month_supply_total = $buy_last_month_supply_house + $buy_last_month_supply_unit;

    // Historic Sale Data
    try {
        error_log('trying new client market');
        $client = new MarketClient;
        $params = [
            'suburb' => $suburb,
            'state' => $state,
            'postcode' => $postcode,
            'propertyTypes' => ['house', 'unit'],
        ];
        $historicSaleData = $client->getHistoricMarketData('sale', 'median-sale-price', $params);
        error_log( print_r($historicSaleData), true);
    } catch (\Exception $e) {
        error_log('Error fetching historic sale data: '.$e->getMessage());
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

    // We'll accumulate all 4 years of data in this array
    $historicSaleData = [];

    // Loop over the last 4 years
    // For clarity, we'll go from oldest (4 years ago) to newest (1 year ago).
    for ($i = 4; $i > 0; $i--) {

        // For i = 4 => covers "4 years ago" to "3 years + 1 day ago"
        // For i = 3 => covers "3 years ago" to "2 years + 1 day ago", etc.
        $start = (new DateTime('first day of last month'))
            ->modify("-{$i} years");
        $end = (clone $start)
            ->modify('+1 year -1 day');

        // Build the params for this specific 1-year block
        $params = [
            'suburb'        => $suburb,
            'postcode'      => $postcode,
            'state'         => $state,
            'propertyTypes' => ['house'],
            'frequency'     => 'monthly',
            'start_date'    => $start->format('Y-m-d'),
            'end_date'      => $end->format('Y-m-d'),
        ];

        error_log('Fetching data from ' . $params['start_date'] . ' to ' . $params['end_date']);

        try {
            // Call the API for this one-year period
            $yearData = $client->getHistoricMarketData('sale', 'median-sale-price', $params);

            // Merge results into our main array
            // Note: This assumes the endpoint returns an array of data that can be merged
            $historicSaleData = array_merge($historicSaleData, $yearData);
        } catch (\Exception $e) {
            error_log('Error fetching historic sale data for year block: ' . $e->getMessage());
        }
    }

    return $historicSaleData;
}
