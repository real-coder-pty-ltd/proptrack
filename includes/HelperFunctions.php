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

    // dd($rentalYield);
    $house_median_rental_yield = last($rentalYield[0]['dateRanges'])['metricValues'][0]['value'];
    $unit_median_rental_yield = last($rentalYield[1]['dateRanges'])['metricValues'][0]['value'];
    // Convert rental yield to percentage with 2 decimal places like this: 5.67%
    $house_median_rental_yield = number_format($house_median_rental_yield * 100, 2).'%';
    $unit_median_rental_yield = number_format($unit_median_rental_yield * 100, 2).'%';


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
    }

    $medianHousePrice = $historicSaleData[0]['dateRanges'][0]['metricValues'][0]['value'];
    $medianUnitPrice = $historicSaleData[1]['dateRanges'][0]['metricValues'][0]['value'];

    // Growth Rate
    $houseGrowthRate = ($historicSaleData[0]['dateRanges'][0]['metricValues'][0]['value'] - $historicSaleData[0]['dateRanges'][1]['metricValues'][0]['value']) / $historicSaleData[0]['dateRanges'][1]['metricValues'][0]['value'];
    $unitGrowthRate = ($historicSaleData[1]['dateRanges'][0]['metricValues'][0]['value'] - $historicSaleData[1]['dateRanges'][1]['metricValues'][0]['value']) / $historicSaleData[1]['dateRanges'][1]['metricValues'][0]['value'];

    $text = sprintf('Last month <span>%s</span> had <span>%d</span> properties available for rent and <span>%d</span> properties for sale. '.
        'Median property prices over the last year range from <span>%s</span> for houses to <span>%s</span> for units. '.
        "If you are looking for an investment property, consider houses in %s rent out for <span>%s</span> with an annual rental yield of <span>%s</span> " .
        "and units rent for <span>%s</span> with a rental yield of <span>%s</span>. <span>%s</span> has seen an annual compound growth rate of <span>%s</span> for houses and <span>%s</span> for units.",
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
        $houseGrowthRate,
        $unitGrowthRate
    );

    return $text;
}