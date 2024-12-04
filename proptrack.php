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

function activate_proptrack()
{
    require_once plugin_dir_path(__FILE__).'src/class-proptrack-activator.php';
    PropTrackActivator::activate();
}

function deactivate_proptrack()
{
    require_once plugin_dir_path(__FILE__).'src/class-proptrack-deactivator.php';
    PropTrackDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_proptrack');
register_deactivation_hook(__FILE__, 'deactivate_proptrack');