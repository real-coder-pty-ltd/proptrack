<?php

namespace RealCoder\Geolocation;

class PostcodeLookup
{
    private $baseUrl = 'http://api.geonames.org/postalCodeSearchJSON';
    private $username;

    public function __construct($username)
    {
        if (empty($username)) {
            throw new \InvalidArgumentException('Geonames username is required.');
        }
        $this->username = $username;
    }

    /**
     * Retrieves the postcode for a given suburb and state.
     *
     * @param string $suburb The name of the suburb.
     * @param string $state The name or abbreviation of the state.
     * @return string|null The postcode if found, or null if not found.
     * @throws \Exception If the request fails or returns an error.
     */
    public function getPostcode($suburb, $state)
    {
        if (empty($suburb) || empty($state)) {
            throw new \InvalidArgumentException('Suburb and state are required.');
        }

        // Build the API query parameters
        $queryParams = [
            'placename' => $suburb,
            'adminCode1' => $state,
            'country' => 'AU', // Australia
            'maxRows' => 1,
            'username' => $this->username,
        ];

        $url = $this->baseUrl . '?' . http_build_query($queryParams);

        // Make the HTTP request
        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            throw new \Exception('HTTP request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        if ($statusCode !== 200) {
            throw new \Exception("API request failed with status code $statusCode: $responseBody");
        }

        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to decode JSON response: ' . json_last_error_msg());
        }

        // Extract the postcode from the API response
        if (!empty($data['postalCodes']) && isset($data['postalCodes'][0]['postalCode'])) {
            return $data['postalCodes'][0]['postalCode'];
        }

        return null; // No postcode found
    }
}