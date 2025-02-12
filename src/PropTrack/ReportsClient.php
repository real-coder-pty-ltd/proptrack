<?php

namespace RealCoder\PropTrack;

class ReportsClient
{
    protected $client;

    public function __construct()
    {
        $this->client = new BaseClient_v1;
    }

    /**
     * Gets an AVM Report for a property.
     */
    public function getReport($propertyId)
    {
        $propertyId = intval($propertyId);

        if (empty($propertyId)) {
            throw new \InvalidArgumentException("Parameter 'propertyId' is required.");
        }

        try {
            $endpoint = '/reports/property';
            $expiresAt = (new \DateTime)->modify('+90 days')->format('Y-m-d');
            $postData = [
                'propertyId' => $propertyId,
                // 'configId' => '46ff6d11-d8b2-40d8-9197-dfa33c61cd6c',
                'expiresAt' => $expiresAt,
                'meta' => [
                    'clientInfo' => [
                        'legalName' => 'Image Property',
                        'abn' => '71 639 714 686',
                    ],
                ],
            ];
            $response = $this->client->post($endpoint, $postData);

            return $response;
        } catch (\Exception $e) {
            // echo 'Error: ' . $e->getMessage();
        }

        return null; // Ensure a return value in case of an exception
    }
}
