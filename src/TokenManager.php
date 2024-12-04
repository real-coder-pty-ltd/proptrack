<?php

namespace RealCoder;

class TokenManager
{
    private $clientOptionName = 'proptrack_client';
    private $secretOptionName = 'proptrack_secret';
    private $tokenOptionName = 'proptrack_bearer_token';
    private $tokenExpiryOptionName = 'proptrack_token_expiry';

    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        // Retrieve client ID and secret from the WordPress database
        $this->clientId = get_option($this->clientOptionName);
        $this->clientSecret = get_option($this->secretOptionName);

        if (!$this->clientId || !$this->clientSecret) {
            throw new \Exception('PropTrack client ID or secret not configured in WordPress options.');
        }
    }

    /**
     * Retrieves the bearer token, refreshing it if expired or absent.
     *
     * @return string
     * @throws \Exception
     */
    public function getBearerToken()
    {
        $token = get_option($this->tokenOptionName);
        $expiry = get_option($this->tokenExpiryOptionName);

        if ($token && $expiry && time() < $expiry) {
            // Token is valid
            return $token;
        }

        // Token is expired or doesn't exist; generate a new one
        return $this->generateBearerToken();
    }

    /**
     * Generates a new bearer token and stores it with its expiry.
     *
     * @return string
     * @throws \Exception
     */
    private function generateBearerToken()
    {
        $url = 'https://data.proptrack.com/oauth2/token';
        $authString = base64_encode($this->clientId . ':' . $this->clientSecret);

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $authString,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $body = 'grant_type=client_credentials';

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Failed to connect to PropTrack API: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            $errorBody = wp_remote_retrieve_body($response);
            throw new \Exception("PropTrack API error (HTTP $statusCode): $errorBody");
        }

        $responseBody = wp_remote_retrieve_body($response);
        $data = json_decode($responseBody, true);

        if (empty($data['access_token']) || empty($data['expires_in'])) {
            throw new \Exception('Invalid response from PropTrack API.');
        }

        $token = $data['access_token'];
        $expiresIn = (int) $data['expires_in'];
        $expiryTime = time() + $expiresIn;

        // Store token and expiry time in WordPress options
        update_option($this->tokenOptionName, $token);
        update_option($this->tokenExpiryOptionName, $expiryTime);

        return $token;
    }
}