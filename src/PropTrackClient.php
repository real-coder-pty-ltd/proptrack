<?php

namespace RealCoder;

class PropTrackClient
{
    private $tokenManager;
    private $baseUrl = 'https://data.proptrack.com/api/v2';

    public function __construct()
    {
        $this->tokenManager = new TokenManager();
    }

    /**
     * Makes a GET request to the specified PropTrack API endpoint with caching.
     *
     * @param string $endpoint The API endpoint (e.g., '/address/match').
     * @param array  $queryParams Optional query parameters.
     * @return array The JSON-decoded response.
     * @throws \Exception If the request fails.
     */
    public function get($endpoint, $queryParams = [])
    {
        $cacheKey = $this->getCacheKey($endpoint, $queryParams);

        // Check if cached data exists
        $cachedData = $this->getCachedData($cacheKey);
        if ($cachedData !== false) {
            error_log(print_r('Using cached data', true));
            return $cachedData;
        }

        // Make the API request
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if (!empty($queryParams)) {
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

    /**
     * Makes a POST request to the specified PropTrack API endpoint.
     *
     * @param  string  $endpoint  The API endpoint.
     * @param  array  $bodyParams  The POST body parameters.
     * @return array The JSON-decoded response.
     *
     * @throws \Exception If the request fails.
     */
    public function post($endpoint, $bodyParams = [])
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        $bearerToken = $this->tokenManager->getBearerToken();

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$bearerToken,
            'Content-Type' => 'application/json',
        ];

        $body = json_encode($bodyParams);

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => $body,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('HTTP request failed: '.$response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        if ($statusCode !== 200 && $statusCode !== 201) {
            throw new \Exception("API request failed with status code $statusCode: $responseBody");
        }

        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to decode JSON response: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Generates a cache key based on the endpoint and query parameters.
     *
     * @param string $endpoint
     * @param array  $params
     * @return string
     */
    private function getCacheKey($endpoint, $params)
    {
        return md5($endpoint . serialize($params));
    }

    /**
     * Retrieves cached data based on the cache key.
     *
     * @param string $cacheKey
     * @return mixed Cached data or false if not found.
     */
    private function getCachedData($cacheKey)
    {
        $cache = false;
    
        if (is_singular()) {
            $postId = get_queried_object_id();
            $cache = get_post_meta($postId, $cacheKey, true);
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            $cache = get_term_meta($term->term_id, $cacheKey, true);
        } elseif (is_admin()) {
            $cache = get_option($cacheKey);
        } else {
            $cache = get_transient($cacheKey);
        }
    
        if ($cache) {
            $cacheData = maybe_unserialize($cache);
    
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
    
    private function cacheData($cacheKey, $data)
    {
        $expiration = time() + HOUR_IN_SECONDS; // Set cache expiration time as needed
        $cacheData = [
            'data' => $data,
            'expiration' => $expiration,
        ];
        $serializedData = maybe_serialize($cacheData);
    
        if (is_singular()) {
            $postId = get_queried_object_id();
            update_post_meta($postId, $cacheKey, $serializedData);
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            update_term_meta($term->term_id, $cacheKey, $serializedData);
        } elseif (is_admin()) {
            update_option($cacheKey, $serializedData);
        } else {
            // For other contexts, use transient with expiration
            set_transient($cacheKey, $cacheData['data'], HOUR_IN_SECONDS);
        }
    }
    
    private function deleteCachedData($cacheKey)
    {
        if (is_singular()) {
            $postId = get_queried_object_id();
            delete_post_meta($postId, $cacheKey);
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            delete_term_meta($term->term_id, $cacheKey);
        } elseif (is_admin()) {
            delete_option($cacheKey);
        } else {
            delete_transient($cacheKey);
        }
    }
}