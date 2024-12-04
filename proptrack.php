<?php

/**
 * @wordpress-plugin
 * Plugin Name:       PropTrack API Integration
 * Plugin URI:        https://realcoder.com.au
 * Description:       Integrate PropTrack's API with WordPress.
 * Version:           0.0.1
 * Author:            Matthew Neal
 * Author URI:        https://realcoder.com.au
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       proptrack
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    exit;
}

define('PROPTRACK_VERSION', '0.0.1');

require 'vendor/autoload.php';

use RealCoder\TokenManager;
use RealCoder\BoundaryFetcher;
use RealCoder\PropTrackClient;

function activate_proptrack()
{
    require_once plugin_dir_path(__FILE__).'includes/PropTrackActivator.php';
    RealCoder\PropTrackActivator::activate();
}

function deactivate_proptrack()
{
    require_once plugin_dir_path(__FILE__).'includes/PropTrackDeactivator.php';
    RealCoder\PropTrackDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_proptrack');
register_deactivation_hook(__FILE__, 'deactivate_proptrack');

require plugin_dir_path(__FILE__).'includes/admin-options.php';
require plugin_dir_path(__FILE__).'includes/HelperFunctions.php';

function fetch_address_match()
{
    if (is_admin()) {
        return; // Avoid running in admin area unless intended
    }

    try {
        $client = new PropTrackClient();

        $endpoint = '/address/match';
        $queryParams = [
            'q' => '5 picasso place mackenzie',
        ];

        $response = $client->get($endpoint, $queryParams);

        var_dump($response); die;

    } catch (\Exception $e) {
        error_log('Error fetching address match: ' . $e->getMessage());
    }
}

add_action('wp', 'fetch_address_match');