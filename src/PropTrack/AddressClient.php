<?php
/**
 * 
 */
namespace RealCoder\PropTrack;

class AddressClient extends BaseClient
{
    /**
     * Gets address suggestions based on a partial address input.
     *
     * @param string $query The partial address input.
     * @return array The JSON-decoded response.
     * @throws \Exception If the request fails.
     */
    public function getAddressSuggestions($query)
    {
        $endpoint = '/address/suggest';
        $queryParams = [
            'q' => $query,
        ];

        return $this->get($endpoint, $queryParams);
    }

    /**
     * Matches an address to get detailed information.
     *
     * @param string $query The address input to match.
     * @return array The JSON-decoded response.
     * @throws \Exception If the request fails.
     */
    public function matchAddress($query)
    {
        $endpoint = '/address/match';
        $queryParams = [
            'q' => $query,
        ];

        return $this->get($endpoint, $queryParams);
    }
}