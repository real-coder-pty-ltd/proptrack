<?php

namespace RealCoder\PropTrack;

use RealCoder\TokenManager;

class BaseClient_v1
{
    protected $tokenManager;

    protected $baseUrl = 'https://data.proptrack.com/api/v1';

    public function __construct()
    {
        $this->tokenManager = new TokenManager;
    }

    public function get($endpoint, $queryParams = [])
    {
        $cacheKey = $this->getCacheKey($endpoint, $queryParams);

        // Check if cached data exists
        $cachedData = $this->getCachedData($cacheKey);

        if ($cachedData !== false) {
            return $cachedData;
        }

        // Make the API request
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if (! empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $bearerToken = $this->tokenManager->getBearerToken();

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $bearerToken,
        ];

        $response = wp_remote_get($url, [
            'headers' => $headers,
            'timeout' => 15,
        ]);

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

        // Cache the data
        $this->cacheData($cacheKey, $data);

        return $data;
    }

    public function post($endpoint, $postData = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $bearerToken = $this->tokenManager->getBearerToken();

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json',
        ];

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => json_encode($postData),
            'timeout' => 30,
        ]);

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

        return $data;
    }

    protected function getCacheKey($endpoint, $params)
    {
        return 'proptrack_' . md5($endpoint . serialize($params));
    }

    protected function getCachedData($cacheKey)
    {
        // Retrieve cached data from wp_options
        $cached = get_option($cacheKey, false);

        if ($cached) {
            $cacheData = maybe_unserialize($cached);

            // Check for expiration
            if (isset($cacheData['expiration']) && time() < $cacheData['expiration']) {
                return $cacheData['data'];
            } else {
                // Cache expired, delete it
                $this->deleteCachedData($cacheKey);
            }
        }

        return false;
    }

    protected function cacheData($cacheKey, $data)
    {
        $expiration = time() + HOUR_IN_SECONDS; // Set cache expiration
        $cacheData = [
            'data' => $data,
            'expiration' => $expiration,
        ];

        // Store in wp_options
        update_option($cacheKey, maybe_serialize($cacheData), false); // false = autoload off
    }

    protected function deleteCachedData($cacheKey)
    {
        delete_option($cacheKey);
    }
}
