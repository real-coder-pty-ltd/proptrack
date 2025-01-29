<?php
/**
 * Class Boundary Fetcher
 */
namespace RealCoder;

use RealCoder\TokenManager\BaseClient;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get local schools via Open Street Maps API.
 * Example:
 * curl -X GET "https://overpass-api.de/api/interpreter?data=[out:json];node(around:10000,-27.543806382017088,153.12682538407537)[amenity=school];out;"
 */
class LocalSchools extends BoundaryFetcher
{
    public $localSchools;

    public $schools;

    public function __construct($suburb)
    {
        parent::__construct($suburb);
        $this->queryOpenStreetMaps();

        return $this;
    }

    public function queryOpenStreetMaps()
    {
        $query = <<<EOT
    [out:json];
    node(around:10000,{$this->latitude},{$this->longitude})[amenity=school];
    out;
    EOT;

    // TODO: Cache the data.
    // $client = new BaseClient();
    // $client->get($url, $query);
    
        // Ensure Overpass API receives a GET request
        $url = "{$this->url}?data=" . urlencode($query);
    
        // Perform GET request
        $response = wp_remote_get($url);
    
        if (is_wp_error($response)) {
            $this->is_error = true;
            return [
                'error' => true,
                'message' => $response->get_error_message(),
            ];
        }
    
        $data = json_decode(wp_remote_retrieve_body($response), true);
    
        if (empty($data['elements'])) {
            return [
                'error' => true,
                'message' => 'No schools found in the suburb.',
            ];
        }
        $this->localSchools = $data['elements'];

    }
    
}
