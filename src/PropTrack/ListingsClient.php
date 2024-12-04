<?php

namespace RealCoder\PropTrack;

class ListingsClient extends BaseClient
{
    /**
     * Retrieves a listing by its ID.
     *
     * @param int $listingId The ID of the listing to retrieve.
     * @return array The JSON-decoded response containing the listing details.
     * @throws \Exception If the request fails.
     */
    public function getListingById($listingId)
    {
        $listingId = intval($listingId);

        if ($listingId <= 0) {
            throw new \InvalidArgumentException('Listing ID must be a positive integer.');
        }

        $endpoint = '/listings/' . $listingId;

        return $this->get($endpoint);
    }

    /**
     * Searches listings by point and radius.
     *
     * @param array $params Associative array of query parameters.
     * @return array The JSON-decoded response containing the listings.
     * @throws \Exception If the request fails or required parameters are missing.
     */
    public function searchListingsByPointAndRadius(array $params)
    {
        $endpoint = '/listings/search/point-and-radius';

        // Validate required parameters
        $requiredParams = ['listingTypes', 'pointType'];

        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                throw new \InvalidArgumentException("Parameter '$param' is required.");
            }
        }

        // Validate 'listingTypes'
        $allowedListingTypes = ['sale', 'rent'];
        $listingTypes = is_array($params['listingTypes']) ? $params['listingTypes'] : explode(',', $params['listingTypes']);
        foreach ($listingTypes as $type) {
            if (!in_array($type, $allowedListingTypes, true)) {
                throw new \InvalidArgumentException("Invalid listing type '$type'. Allowed values are 'sale', 'rent'.");
            }
        }
        $params['listingTypes'] = implode(',', $listingTypes); // Ensure it's a comma-separated string

        // Validate 'pointType' and related parameters
        $allowedPointTypes = ['propertyId', 'latLong'];
        if (!in_array($params['pointType'], $allowedPointTypes, true)) {
            throw new \InvalidArgumentException("Invalid point type '{$params['pointType']}'. Allowed values are 'propertyId', 'latLong'.");
        }

        if ($params['pointType'] === 'latLong') {
            if (empty($params['latitude']) || empty($params['longitude'])) {
                throw new \InvalidArgumentException("Parameters 'latitude' and 'longitude' are required when 'pointType' is 'latLong'.");
            }
        } elseif ($params['pointType'] === 'propertyId') {
            if (empty($params['propertyId'])) {
                throw new \InvalidArgumentException("Parameter 'propertyId' is required when 'pointType' is 'propertyId'.");
            }
        }

        // Validate other parameters if needed (e.g., allowed values, data types)

        // Make the API request
        return $this->get($endpoint, $params);
    }
}