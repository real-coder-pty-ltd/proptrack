<?php

namespace RealCoder\PropTrack;

class MarketClient extends BaseClient
{
    /**
     * Retrieves auction results based on search criteria.
     *
     * @param  array  $params  Associative array of query parameters.
     * @return array The JSON-decoded response containing auction results.
     *
     * @throws \Exception If the request fails or required parameters are missing.
     */
    public function getAuctionResults(array $params)
    {
        $endpoint = '/market/auctions';

        // Validate required parameter 'searchType'
        if (empty($params['searchType'])) {
            throw new \InvalidArgumentException("Parameter 'searchType' is required.");
        }

        // Validate 'searchType' and related parameters
        $allowedSearchTypes = ['suburb', 'state', 'gccsa'];
        $searchType = $params['searchType'];

        if (! in_array($searchType, $allowedSearchTypes, true)) {
            throw new \InvalidArgumentException("Invalid 'searchType' value '$searchType'. Allowed values are 'suburb', 'state', 'gccsa'.");
        }

        // Initialize required parameters based on 'searchType'
        if ($searchType === 'suburb') {
            if (empty($params['suburb']) || empty($params['state']) || empty($params['postcode'])) {
                throw new \InvalidArgumentException("Parameters 'suburb', 'state', and 'postcode' are required when 'searchType' is 'suburb'.");
            }
        } elseif ($searchType === 'state') {
            if (empty($params['state'])) {
                throw new \InvalidArgumentException("Parameter 'state' is required when 'searchType' is 'state'.");
            }
        } elseif ($searchType === 'gccsa') {
            if (empty($params['gccsaCode'])) {
                throw new \InvalidArgumentException("Parameter 'gccsaCode' is required when 'searchType' is 'gccsa'.");
            }
        }

        // Validate 'state' parameter if provided
        if (isset($params['state'])) {
            $allowedStates = ['act', 'nsw', 'nt', 'qld', 'sa', 'tas', 'vic', 'wa'];
            if (! in_array(strtolower($params['state']), $allowedStates, true)) {
                throw new \InvalidArgumentException("Invalid 'state' value '{$params['state']}'. Allowed values are 'act', 'nsw', 'nt', 'qld', 'sa', 'tas', 'vic', 'wa'.");
            }
            $params['state'] = strtolower($params['state']); // Ensure state is in lowercase
        }

        // Validate date parameters if provided
        if (isset($params['startDate'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['startDate'])) {
                throw new \InvalidArgumentException("Invalid 'startDate' format. Expected YYYY-MM-DD.");
            }
        }
        if (isset($params['endDate'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['endDate'])) {
                throw new \InvalidArgumentException("Invalid 'endDate' format. Expected YYYY-MM-DD.");
            }
        }

        // Validate 'postcode' if provided
        if (isset($params['postcode'])) {
            $params['postcode'] = intval($params['postcode']);
            if ($params['postcode'] <= 0 || $params['postcode'] > 9999) {
                throw new \InvalidArgumentException("Invalid 'postcode' value '{$params['postcode']}'. It must be a 4-digit number.");
            }
        }

        // Make the API request
        return $this->get($endpoint, $params);
    }

    /**
     * Retrieves historic market data for rent or sale metrics.
     *
     * @param  string  $type  The market type ('rent' or 'sale').
     * @param  string  $metric  The market metric to retrieve.
     * @param  array  $params  Associative array of query parameters.
     * @return array The JSON-decoded response containing market data.
     *
     * @throws \Exception If the request fails or required parameters are missing.
     */
    public function getHistoricMarketData($type, $metric, array $params)
    {
        // Validate 'type'
        $allowedTypes = ['rent', 'sale'];
        if (! in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid market type '$type'. Allowed values are 'rent', 'sale'.");
        }

        // Validate 'metric'
        $allowedMetrics = [
            'rent' => ['median-rental-yield', 'rental-transaction-volume', 'median-rental-price', 'median-days-on-market'],
            'sale' => ['sale-transaction-volume', 'median-sale-price', 'median-days-on-market'],
        ];

        if (! in_array($metric, $allowedMetrics[$type], true)) {
            throw new \InvalidArgumentException("Invalid metric '$metric' for market type '$type'.");
        }

        // Construct the endpoint
        $endpoint = "/market/{$type}/historic/{$metric}";

        // Validate required parameters
        $requiredParams = ['suburb', 'state', 'postcode', 'propertyTypes'];
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                throw new \InvalidArgumentException("Parameter '$param' is required.");
            }
        }

        // Validate 'state'
        $allowedStates = ['act', 'nsw', 'nt', 'qld', 'sa', 'tas', 'vic', 'wa'];
        if (! in_array(strtolower($params['state']), $allowedStates, true)) {
            throw new \InvalidArgumentException("Invalid 'state' value '{$params['state']}'. Allowed values are ".implode(', ', $allowedStates).'.');
        }
        $params['state'] = strtolower($params['state']); // Ensure state is in lowercase

        // Validate 'propertyTypes'
        $allowedPropertyTypes = ['house', 'unit'];
        $propertyTypes = is_array($params['propertyTypes']) ? $params['propertyTypes'] : explode(',', $params['propertyTypes']);
        foreach ($propertyTypes as $typeItem) {
            if (! in_array($typeItem, $allowedPropertyTypes, true)) {
                throw new \InvalidArgumentException("Invalid property type '$typeItem'. Allowed values are 'house', 'unit'.");
            }
        }
        $params['propertyTypes'] = implode(',', $propertyTypes); // Ensure it's a comma-separated string

        // Validate 'postcode'
        $params['postcode'] = intval($params['postcode']);
        if ($params['postcode'] <= 0 || $params['postcode'] > 9999) {
            throw new \InvalidArgumentException("Invalid 'postcode' value '{$params['postcode']}'. It must be a 4-digit number.");
        }

        // Validate 'frequency' if provided
        if (isset($params['frequency'])) {
            $allowedFrequencies = ['yearly', 'monthly'];
            if (! in_array($params['frequency'], $allowedFrequencies, true)) {
                throw new \InvalidArgumentException("Invalid 'frequency' value '{$params['frequency']}'. Allowed values are 'yearly', 'monthly'.");
            }
        }

        // Validate date parameters if provided
        if (isset($params['startDate'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['startDate'])) {
                throw new \InvalidArgumentException("Invalid 'startDate' format. Expected YYYY-MM-DD.");
            }
        }
        if (isset($params['endDate'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['endDate'])) {
                throw new \InvalidArgumentException("Invalid 'endDate' format. Expected YYYY-MM-DD.");
            }
        }

        // Make the API request
        return $this->get($endpoint, $params);
    }

    /**
     * Retrieves supply and demand data based on the metric.
     *
     * @param  string  $metric  The supply and demand metric to retrieve.
     * @param  array  $params  Associative array of query parameters.
     * @return array The JSON-decoded response containing supply and demand data.
     *
     * @throws \Exception If the request fails or required parameters are missing.
     */
    public function getSupplyAndDemandData($metric, array $params)
    {
        // Validate 'metric'
        $allowedMetrics = ['potential-buyers', 'potential-renters'];
        if (! in_array($metric, $allowedMetrics, true)) {
            throw new \InvalidArgumentException("Invalid metric '$metric'. Allowed values are 'potential-buyers', 'potential-renters'.");
        }

        // Construct the endpoint
        $endpoint = "/market/supply-and-demand/{$metric}";

        // Validate required parameters
        $requiredParams = ['suburb', 'state', 'postcode', 'propertyTypes'];
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                throw new \InvalidArgumentException("Parameter '$param' is required.");
            }
        }

        // Validate 'state'
        $allowedStates = ['act', 'nsw', 'nt', 'qld', 'sa', 'tas', 'vic', 'wa'];
        if (! in_array(strtolower($params['state']), $allowedStates, true)) {
            throw new \InvalidArgumentException("Invalid 'state' value '{$params['state']}'. Allowed values are ".implode(', ', $allowedStates).'.');
        }
        $params['state'] = strtolower($params['state']); // Ensure state is in lowercase

        // Validate 'propertyTypes'
        $allowedPropertyTypes = ['house', 'unit'];
        $propertyTypes = is_array($params['propertyTypes']) ? $params['propertyTypes'] : explode(',', $params['propertyTypes']);
        foreach ($propertyTypes as $typeItem) {
            if (! in_array($typeItem, $allowedPropertyTypes, true)) {
                throw new \InvalidArgumentException("Invalid property type '$typeItem'. Allowed values are 'house', 'unit'.");
            }
        }
        $params['propertyTypes'] = implode(',', $propertyTypes); // Ensure it's a comma-separated string

        // Validate 'postcode'
        $params['postcode'] = intval($params['postcode']);
        if ($params['postcode'] <= 0 || $params['postcode'] > 9999) {
            throw new \InvalidArgumentException("Invalid 'postcode' value '{$params['postcode']}'. It must be a 4-digit number.");
        }

        // Validate 'frequency' if provided
        if (isset($params['frequency'])) {
            $allowedFrequencies = ['yearly', 'monthly'];
            if (! in_array($params['frequency'], $allowedFrequencies, true)) {
                throw new \InvalidArgumentException("Invalid 'frequency' value '{$params['frequency']}'. Allowed values are 'yearly', 'monthly'.");
            }
        }

        // Validate date parameters if provided
        if (isset($params['startDate'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['startDate'])) {
                throw new \InvalidArgumentException("Invalid 'startDate' format. Expected YYYY-MM-DD.");
            }
        }
        if (isset($params['endDate'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['endDate'])) {
                throw new \InvalidArgumentException("Invalid 'endDate' format. Expected YYYY-MM-DD.");
            }
        }

        // Make the API request
        return $this->get($endpoint, $params);
    }
}
