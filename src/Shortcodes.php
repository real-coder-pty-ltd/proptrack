<?php
/**
 * Shortcodes for the Real Coder PropTrack Connector plugin.
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Shortcode to display the suburb description.
 */
function rcptc_suburb_description(string $suburb, string $state = 'qld'): string|array
{
    global $wpdb;

    $data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}proptrack_suburbs WHERE locality = %s AND state = %s COLLATE utf8mb4_general_ci LIMIT 1",
            $suburb,
            $state
        )
    );

    if (! $suburb) {
        return 'not found.';
    }

    $suburb_name = ucfirst(strtolower($data->locality));

    $args = [
        'postcode' => $data->postcode,
        'state' => $state,
        'suburb' => $suburb_name,
        'propertyTypes' => 'house,unit',
        'frequency' => 'monthly',
        'start_date' => (new DateTime('first day of last month'))->format('Y-m-d'),
        'end_date' => (new DateTime('last day of last month'))->format('Y-m-d'),
    ];

    // Connect to the PropTrack API and check for credentials.
    $connect = new \RealCoder\PropTrack\API();

    $MarketClient = new RealCoder\PropTrack\MarketClient();
    $supply_and_demand = getSupplyAndDemandData('potential-renters', [$suburb_name, $state, $data->postcode, $propertyTypes]);

    var_dump($supply_and_demand);

    // $buy = $proptrack
    //     ->endpoint('/v2/market/supply-and-demand/{metric}')
    //     ->context('suburb')
    //     ->metric('potential-buyers')
    //     ->query($args)
    //     ->request();

    // $medianHousePrice = $proptrack
    //     ->endpoint('/v2/market/sale/historic/{metric}')
    //     ->context('suburb')
    //     ->metric('median-sale-price')
    //     ->query($args)
    //     ->request();

    // dd($rent);

    $rent_last_month_supply_house = last(last($rent[0]->dateRanges)->metricValues)->supply;
    $rent_last_month_supply_unit = last(last($rent[1]->dateRanges)->metricValues)->supply;
    $rent_last_month_supply_total = $rent_last_month_supply_house + $rent_last_month_supply_unit;

    $buy_last_month_supply_house = last(last($buy[0]->dateRanges)->metricValues)->supply;
    $buy_last_month_supply_unit = last(last($buy[1]->dateRanges)->metricValues)->supply;
    $buy_last_month_supply_total = $buy_last_month_supply_house + $buy_last_month_supply_unit;


    $text = sprintf("Last month <span>%s</span> had <span>%d</span> properties available for rent and <span>%d</span> properties for sale. ".
        "Median property prices over the last year range from <span>%s</span> for houses to <span>%s</span> for units. " .
        // "If you are looking for an investment property, consider houses in %s rent out for <span>%s</span> with an annual rental yield of <span>%s</span> " .
        // "and units rent for <span>%s</span> with a rental yield of <span>%s</span>. <span>%s</span> has seen an annual compound growth rate of <span>%s</span> for houses and <span>%s</span> for units.",
        $suburb_name,
        $rent_last_month_supply_total,
        $buy_last_month_supply_total,
        // $medianHousePrice,
        // $medianUnitPrice,
        // $area,
        // $houseRent,
        // $houseYield,
        // $unitRent,
        // $unitYield,
        // $area,
        // $houseGrowthRate,
        // $unitGrowthRate
    );
    return $text;
}