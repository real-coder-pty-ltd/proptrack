<?php
/**
 * Example functions to use the PropTrack API.
 */

use RealCoder\PropTrack\MarketClient;
use RealCoder\PropTrack\ListingsClient;
use RealCoder\PropTrack\AddressClient;

function fetch_address_suggestions($address)
{
    try {
        $client = new AddressClient();
        $suggestions = $client->getAddressSuggestions($address);

        foreach ($suggestions as $suggestion) {
            dump($suggestion);
        }
    } catch (\Exception $e) {
        error_log('Error fetching address suggestions: ' . $e->getMessage());
    }
}

function fetch_listing_by_id()
{
    try {
        $client = new ListingsClient();
        $listingId = 123456; // Replace with actual listing ID
        $listing = $client->getListingById($listingId);

        // Handle the listing data
        echo 'Listing ID: ' . $listing['listingId'] . '<br>';
    } catch (\Exception $e) {
        error_log('Error fetching listing: ' . $e->getMessage());
    }
}

function fetch_auction_results()
{
    try {
        $client = new MarketClient();
        $params = [
            'searchType' => 'suburb',
            'suburb' => 'Berwick',
            'state' => 'vic',
            'postcode' => 3806,
            'startDate' => '2023-08-01',
            'endDate' => '2023-09-30',
        ];
        $auctionResults = $client->getAuctionResults($params);

        foreach ($auctionResults as $result) {
            echo 'Auction Date: ' . $result['auctionDate'] . '<br>';
        }
    } catch (\Exception $e) {
        error_log('Error fetching auction results: ' . $e->getMessage());
    }
}

function fetch_historic_sale_data()
{
    try {
        $client = new MarketClient();
        $type = 'sale';
        $metric = 'median-sale-price';
        $params = [
            'suburb' => 'Sydney',
            'state' => 'nsw',
            'postcode' => 2000,
            'propertyTypes' => ['house', 'unit'],
        ];
        $marketData = $client->getHistoricMarketData($type, $metric, $params);

        foreach ($marketData as $dataPoint) {
            echo 'Date: ' . $dataPoint['date'] . '<br>';
            echo 'Median Sale Price: ' . $dataPoint['value'] . '<br>';
        }
    } catch (\Exception $e) {
        error_log('Error fetching historic sale data: ' . $e->getMessage());
    }
}

function fetch_supply_and_demand_data()
{
    try {
        $client = new MarketClient();
        $metric = 'potential-buyers';
        $params = [
            'suburb' => 'Sydney',
            'state' => 'nsw',
            'postcode' => 2000,
            'propertyTypes' => ['house', 'unit'],
        ];
        $supplyDemandData = $client->getSupplyAndDemandData($metric, $params);

        foreach ($supplyDemandData as $dataPoint) {
            echo 'Date: ' . $dataPoint['date'] . '<br>';
            echo 'Value: ' . $dataPoint['value'] . '<br>';
        }
    } catch (\Exception $e) {
        error_log('Error fetching supply and demand data: ' . $e->getMessage());
    }
}