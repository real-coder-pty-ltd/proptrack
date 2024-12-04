<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 */
namespace RealCoder;

class PropTrack
{
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct()
    {
        if (defined('PROPTRACK_VERSION')) {
            $this->version = PROPTRACK_VERSION;
        }
        
        $this->plugin_name = 'proptrack';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)).'class-proptrack-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)).'class-proptrack-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)).'class-proptrack-public.php';

        $this->loader = new PropTrackLoader();

    }

    private function define_admin_hooks()
    {

        $plugin_admin = new PropTrackAdmin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

    }

    private function define_public_hooks()
    {

        $plugin_public = new PropTrackPublic($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_version()
    {
        return $this->version;
    }
}