<?php
/**
 * The public-facing functionality of the plugin.
 */
namespace RealCoder;

class PropTrackPublic
{
    private $plugin_name;

    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__).'css/proptrack-public.css', [], $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__).'js/proptrack-public.js', ['jquery'], $this->version, true);

        $enqueue_google_maps = get_option('proptrack_enqueue_google_maps');
        if ($enqueue_google_maps) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key='.get_option('proptrack_gmaps_api_key').'&amp;libraries=places', [], $this->version, false);
        }
    }
}