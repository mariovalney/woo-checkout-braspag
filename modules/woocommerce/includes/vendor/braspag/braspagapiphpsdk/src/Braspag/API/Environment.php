<?php
namespace Braspag\API;

class Environment
{
    private $api;

    private $apiQuery;

    private function __construct($api, $apiQuery)
    {
        $this->api = $api;
        $this->apiQuery = $apiQuery;
    }

    public static function sandbox()
    {
        $api = 'https://apisandbox.braspag.com.br/';
        $apiQuery = 'https://apiquerysandbox.braspag.com.br/';

        return new Environment($api, $apiQuery);
    }

    public static function production()
    {
        $api = 'https://api.braspag.com.br/';
        $apiQuery = 'https://apiquery.braspag.com.br/';

        return new Environment($api, $apiQuery);
    }

    /**
     * Gets the environment's Api URL
     *
     * @return the Api URL
     */
    public function getApiUrl()
    {
        return $this->api;
    }

    /**
     * Gets the environment's Api Query URL
     *
     * @return the Api Query URL
     */
    public function getApiQueryURL()
    {
        return $this->apiQuery;
    }
}
