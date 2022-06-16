<?php

class base
{
    /**
     * @var connecti
     */
    private $db;

    function __construct()
    {
        $this->db = new connecti();
    }

    private function dataResponse(array $data) : array
    {
        ob_start();
        header('Content-type: application/json; charset=utf-8');
        print json_encode($data);
        exit();
    }

    public function countries() : array {
        $countries = $this->db->get_res("
        SELECT country.id,
               full_name name,
               UPPER(short_name) shortname,
               marketplace_name amz_marketplace_url
        FROM country
        INNER JOIN amazon_api_config_by_countries aacbc on country.id = aacbc.country_id
    ");
        $typedCountries = array_map(function ($country) {
            return [
                'id' => (int)$country['id'],
                'name' => (string)$country['name'],
                'shortname' => (string)$country['shortname'],
                'amz_marketplace_url' => (string)$country['amz_marketplace_url']
            ];
        }, $countries);

        $this->dataResponse($typedCountries);
    }
}