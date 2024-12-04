<?php
/**
 * The admin-specific functionality of the plugin.
 */
namespace RealCoder;

class PropTrackAdmin
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
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__).'css/proptrack-admin.css', [], $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__).'js/proptrack-admin.js', ['jquery'], $this->version, false);
    }
}