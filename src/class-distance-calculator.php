<?php

class DistanceCalculator
{
    private string $apiKey;
    private array $travelModes = ['driving', 'transit', 'walking', 'bicycling'];
    public array $data;
    private array $messages;
    private string $meta_key;

    public function __construct()
    {
        $this->apiKey = get_option('proptrack_gmaps_server_api_key');

        if ( ! $this->apiKey) {
            return new WP_Error('api_error', 'Server Side Google Maps API key is missing. Please set it in the settings.');
        }
    }

    public function calculateAndSaveDistances(int $origin_id, int $destination_id): array
    {
        $distanceData = get_post_meta($origin_id, $destination_id.'_distance_data', true);

        if ($distanceData) {
            $this->data[$destination_id] = $distanceData;
            $this->meta_key = $destination_id.'_distance_data';
            $this->messages[$destination_id] = 'Data already exists. API request was skipped.';
            return $this->messages;
        }

        $distanceData = [];

        foreach ($this->travelModes as $mode) {

            $origin_lat = get_post_meta($origin_id, 'proptrack_suburb_lat', true);
            $origin_long = get_post_meta($origin_id, 'proptrack_suburb_lng', true);
            $origin = $origin_lat . ',' . $origin_long;

            $dest_lat = get_post_meta($destination_id, 'proptrack_suburb_lat', true);
            $dest_long = get_post_meta($destination_id, 'proptrack_suburb_lng', true);
            $destination = $dest_lat . ',' . $dest_long;

            $result = $this->getDistanceData($origin, $destination, $mode);

            if (is_wp_error($result)) {
                $this->messages[$destination_id] = 'Error fetching data: ' . $result->get_error_message();
                continue;
            }

            $distanceData[$mode] = $result;
        }

        if (!empty($distanceData)) {
            update_post_meta($origin_id, $destination_id.'_distance_data', $distanceData);
            // Save it to each post so we don't have to make the request again.
            update_post_meta($destination_id, $origin_id.'_distance_data', $distanceData);
            $this->messages[$destination_id] = 'Distance data successfully retrieved and saved.';
            $this->meta_key = $destination_id.'_distance_data';
            $this->data[$destination_id] = $distanceData;
            return $this->messages;
        }

        $this->messages[$destination_id] = 'Failed to retrieve distance data.';
        return $this->messages;
    }

    /**
     * Retrieves distance and duration data from Google Maps Distance Matrix API.
     *
     * @param string $origin      The origin coordinates in "lat,lng" format.
     * @param string $destination The destination coordinates in "lat,lng" format.
     * @param string $mode        The travel mode (driving, transit, bicycling, walking).
     *
     */
    private function getDistanceData(string $origin, string $destination, string $mode): array|WP_Error
    {
        $url = add_query_arg([
            'origins'      => $origin,
            'destinations' => $destination,
            'mode'         => $mode,
            'key'          => $this->apiKey,
        ], 'https://maps.googleapis.com/maps/api/distancematrix/json');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['status'] !== 'OK') {
            return new WP_Error('api_error', 'API returned an error: ' . $data['status']);
        }

        $element = $data['rows'][0]['elements'][0];

        if ($element['status'] !== 'OK') {
            return new WP_Error('api_error', 'Element returned an error: ' . $element['status']);
        }

        return [
            'distance' => $element['distance']['text'],
            'duration' => $element['duration']['text'],
        ];
    }
}