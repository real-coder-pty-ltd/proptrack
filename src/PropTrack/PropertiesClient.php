<?php

namespace RealCoder\PropTrack;

class PropertiesClient extends BaseClient
{
    /**
     * Retrieves a list of property listings based on search criteria.
     *
     * @param  array  $params  Associative array of query parameters.
     * @return array The JSON-decoded response containing property listings.
     *
     * @throws \Exception If the request fails or required parameters are missing.
     */
    public function getPropertyListings($propertyId)
    {
        $propertyId = intval($propertyId);

        // Validate required parameter
        if (empty($propertyId)) {
            throw new \InvalidArgumentException("Parameter 'propertyId' is required.");
        }

        $endpoint = '/properties/'.$propertyId.'/listings';

        return $this->get($endpoint);
    }
}
