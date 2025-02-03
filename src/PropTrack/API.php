<?php

namespace RealCoder\PropTrack;

class API
{
    private $clientId;
    private $clientSecret;
    private $tokenOptionName = 'proptrack_bearer_token';
    private $tokenExpiryOptionName = 'proptrack_token_expiry';

    public function __construct()
    {
        // Retrieve client ID and secret from the WordPress database
        $this->clientId = get_option('proptrack_client');
        $this->clientSecret = get_option('proptrack_secret');

        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('PropTrack client ID or secret not configured in WordPress options.');
        }
    }

    /**
     * Get the bearer token, either from the database or by generating a new one.
     *
     * @return string Bearer token.
     * @throws \Exception If token generation fails.
     */
    public function getBearerToken(): string
    {
        // Check if a valid token exists
        $token = get_option($this->tokenOptionName);
        $expiryTime = get_option($this->tokenExpiryOptionName);

        if ($token && $expiryTime && time() < $expiryTime) {
            return $token;
        }

        // Generate a new token if none exists or it's expired
        return $this->generateBearerToken();
    }

    /**
     * Generate a new bearer token and store it in the database.
     *
     * @return string Bearer token.
     * @throws \Exception If the request fails.
     */
    private function generateBearerToken(): string
    {
        $url = 'https://data.proptrack.com/oauth2/token';
        $headers = [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $data = http_build_query(['grant_type' => 'client_credentials']);

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => $data,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Failed to connect to PropTrack API: ' . $response->get_error_message());
        }

        $responseBody = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($responseBody, true);

        if (empty($decodedBody['access_token']) || empty($decodedBody['expires_in'])) {
            throw new \Exception('Invalid response from PropTrack API.');
        }

        $token = $decodedBody['access_token'];
        $expiryTime = time() + $decodedBody['expires_in'];

        // Store token and expiry time in WordPress options
        update_option($this->tokenOptionName, $token);
        update_option($this->tokenExpiryOptionName, $expiryTime);

        return $token;
    }
}