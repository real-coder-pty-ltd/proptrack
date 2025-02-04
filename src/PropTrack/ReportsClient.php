<?php

namespace RealCoder\PropTrack;

class ReportsClient extends BaseClient
{
    /**
     * Unique identifier for a property.
     * Use address/match or address/suggest to get the propertyId.
     */
    public int $propertyId;

    /**
     * Unique report configuration identifier, provided by PropTrack
     * Default is a standard PropTrack property report
     */
    public string $configId;

    /**
     * Date after which the report will expire
     * Default is 90 days from date of issue
     * Note: Maximum timeframe is 12 months
     * Format YYYY-MM-DD
     */
    public string $expiresAt;

    /**
     * Custom data or information displayed on a property report, available on a premium report only
     * Some examples may include: name, email or phone number (Contact PropTrack to discuss your requirements)
     */
    public array $meta;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets an AVM Report for a property.
     */
    public function getReport(string $address): self
    {
        // string $propertyId, array $meta, string $configId = '', string $expiresAt = ''
        $address = new AddressClient;
        $addressMatch = $address->matchAddress($address);

        dd($addressMatch);

        $propertyId = intval($propertyId);

        if (empty($propertyId)) {
            throw new \InvalidArgumentException("Parameter 'propertyId' is required.");
        }

        if (! is_array($meta)) {
            throw new \InvalidArgumentException("Parameter 'meta' must be an array.");
        }

        $this->propertyId = $propertyId;

        $endpoint = '/reports/property';

        return $this->get($endpoint);
    }
}
