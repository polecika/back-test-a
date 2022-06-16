<?php

include_once './helpers/formatters.php';

class HelicopterView
{
    /**
     * @var connecti
     */
    private $db;

    public $post;
    public $get;

    private const HANDLED_REVIEW_STATUS = 'Done';

    public function __construct()
    {
        $this->db = new connecti();
    }

    /**
     * JSON formatting method
     * @return array
     */
    public function get_format(): array
    {
        $format['value']['type'] = 'num';
        return $format;
    }

    function get_required($method)
    {
        $required["HVFeedback"] = explode(",", "date_from,date_to");

        if (isset($required[$method])) {
            return $required[$method];
        }
    }

    /**
     * @Route(api-v3/api.php?action=HVCountry)
     */
    public function HVCountry(): array
    {
        if (!isset($this->post['date_from']) || !isset($this->post['date_to'])) {
            return [
                'success' => false,
                'message' => 'Time frames is required'
            ];
        }

        $date_from = microTimeToTime($this->post['date_from']);
        $date_to = microTimeToTime($this->post['date_to']);

        if (is_array($this->post['countries'])) {
            $country_shortnames = arrayToString(array_map('strtolower', $this->post['countries']));
        } else {
            $country_shortnames = '';
        }

        if (is_array($this->post['products'])) {
            $product_nicknames = arrayToString(array_map('strtolower', $this->post['products']));
        } else {
            $product_nicknames = '';
        }

        if (is_bool($this->post['aggregate'])) {
            $isAggregate = $this->post['aggregate'];
        } else {
            $isAggregate = false;
        }

        if ($isAggregate && !empty($product_nicknames)) {
            $product_nicknames = $this->getBusinessProductsByParentShortnames($product_nicknames);
        }

        $products = $this->getProductsCounterData($country_shortnames, $product_nicknames, $date_from, $date_to);

        usort($products, function ($item1, $item2) {
            return $item2['value'] <=> $item1['value'];
        });

        return $products;
    }

    /**
     * @Route(api-v3/api.php?action=HVTopProducts)
     */
    public function HVTopProducts(): array
    {
        if (!isset($this->post['date_from']) || !isset($this->post['date_to'])) {
            return [
                'success' => false,
                'message' => 'Time frames is required'
            ];
        }

        $date_from = microTimeToTime($this->post['date_from']);
        $date_to = microTimeToTime($this->post['date_to']);

        if (is_array($this->post['countries'])) {
            $country_shortnames = arrayToString(array_map('strtolower', $this->post['countries']));
        } else {
            $country_shortnames = '';
        }

        if (is_array($this->post['products'])) {
            $product_nicknames = arrayToString(array_map('strtolower', $this->post['products']));
        } else {
            $product_nicknames = '';
        }

        if (is_bool($this->post['aggregate'])) {
            $isAggregate = $this->post['aggregate'];
        } else {
            return [
                'success' => false,
                'message' => 'Aggregate parameter must be bool'
            ];
        }

        if ($isAggregate) {
            $products = $this->getAggregatedTopProductsCounterData($country_shortnames, $product_nicknames, $date_from, $date_to);
        } else {
            $products = $this->getTopProductsCounterData($country_shortnames, $product_nicknames, $date_from, $date_to);
        }

        usort($products, function ($item1, $item2) {
            return $item2['value'] <=> $item1['value'];
        });

        return ['products' => $products];
    }

    private function getAggregatedTopProductsCounterData($country_shortnames, $product_nicknames, $date_from, $date_to)
    {
        if ($country_shortnames && $product_nicknames) {
            $sql = "SELECT bpp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value`
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bpp.id = bp.parent_product_id
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    AND (bpp.short_name in ('$product_nicknames'))
                    AND c.short_name in ('$country_shortnames')
                GROUP BY bpp.short_name";
        } elseif (!$country_shortnames && $product_nicknames) {
            $sql = "SELECT bpp.short_name as `product_shortname`, 
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value` 
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bpp.id = bp.parent_product_id
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    AND (bpp.short_name in ('$product_nicknames'))
                GROUP BY bpp.short_name";
        } elseif ($country_shortnames && !$product_nicknames) {
            $sql = "SELECT bpp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value` 
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bpp.id = bp.parent_product_id
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    AND c.short_name in ('$country_shortnames')
                GROUP BY bpp.short_name";
        } else {
            $sql = "SELECT bpp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value`  
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bpp.id = bp.parent_product_id
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                GROUP BY bpp.short_name";
        }
        return $this->db->get_res($sql);
    }

    private function getTopProductsCounterData($country_shortnames, $product_nicknames, $date_from, $date_to)
    {
        if ($country_shortnames && $product_nicknames) {
            $sql = "SELECT bp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value`  
                    FROM business_products bp
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    AND (bp.short_name in ('$product_nicknames'))
                    AND c.short_name in ('$country_shortnames')
                GROUP BY bp.short_name";
        } elseif (!$country_shortnames && $product_nicknames) {
            $sql = "SELECT bp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value` 
                    FROM business_products bp
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE   
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    AND (bp.short_name in ('$product_nicknames') OR dr.drid IS NULL)
                GROUP BY bp.short_name";
        } elseif ($country_shortnames && !$product_nicknames) {
            $sql = "SELECT bp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value` 
                    FROM business_products bp
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    AND c.short_name in ('$country_shortnames')
                GROUP BY bp.short_name";
        } else {
            $sql = "SELECT bp.short_name as `product_shortname`,  
                    COUNT(
                        IF(`dr_status` = '" . self::HANDLED_REVIEW_STATUS  . "', dr.drid, NULL)
                    ) as `value`
                    FROM business_products bp
                    LEFT JOIN products p ON bp.id = p.business_product_id
                    LEFT JOIN country c ON c.id = p.country_id
                    LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                WHERE
                    (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                GROUP BY bp.short_name";
        }

        return $this->db->get_res($sql);
    }

    private function getBusinessProductsShortnames(): array
    {
        $sql = "SELECT `short_name` FROM `business_products`";
        return array_column($this->db->get_res($sql), 'short_name');
    }

    private function getBusinessProductsParentShortnames(): array
    {
        $sql = "SELECT `short_name` FROM `business_parent_products`";
        return array_column($this->db->get_res($sql), 'short_name');
    }

    private function getBusinessProductsByParentShortnames($product_shortnames = ''): string
    {
        $sql = "SELECT bp.short_name FROM `business_parent_products` bpp
                JOIN `business_products` bp ON bpp.id = bp.parent_product_id";

        if (!empty($product_shortnames)) {
            $sql .= " WHERE bpp.short_name IN ('$product_shortnames')";
        }

        $shortnames = array_column($this->db->get_res($sql), 'short_name');
        return arrayToString(array_map('strtolower', $shortnames));
    }

    public function getProductsCounterData($country_shortnames, $product_nicknames, $date_from, $date_to)
    {
        if ($country_shortnames && $product_nicknames) {
            $sql = "SELECT c.full_name as `country`, c.short_name as `country_shortname`, 
                    count(dr.dr_country_id) as `value` FROM country c
                        LEFT JOIN products p ON p.country_id = c.id
                        LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                        LEFT JOIN business_products bp ON bp.id = p.business_product_id
                    WHERE 
                        (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL) 
                        AND c.short_name in ('$country_shortnames') 
                        AND (bp.short_name in ('$product_nicknames') OR dr.drid IS NULL)
                    GROUP BY c.short_name";
        } elseif (!$country_shortnames && $product_nicknames) {
            $sql = "SELECT c.full_name as `country`, c.short_name as `country_shortname`, 
                    count(dr.dr_country_id) as `value`FROM country c
                        LEFT JOIN products p ON p.country_id = c.id
                        LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                        LEFT JOIN business_products bp ON bp.id = p.business_product_id
                    WHERE 
                        (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                        AND (bp.short_name in ('$product_nicknames') OR dr.drid IS NULL)
                    GROUP BY c.short_name";
        } elseif ($country_shortnames && !$product_nicknames) {
            $sql = "SELECT c.full_name as `country`, c.short_name as `country_shortname`,
                    count(dr.dr_country_id) as `value` FROM country c
                        LEFT JOIN products p ON p.country_id = c.id
                        LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                    WHERE 
                        (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                        AND c.short_name in ('$country_shortnames') 
                    GROUP BY c.short_name";
        } else {
            $sql = "SELECT c.full_name as `country`, c.short_name as `country_shortname`, 
                    count(dr.dr_country_id) as `value` FROM country c
                        LEFT JOIN products p ON p.country_id = c.id
                        LEFT JOIN data_reviews dr ON dr.dr_product_id = p.id
                    WHERE 
                        (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' OR `dr_date` IS NULL)
                    GROUP BY c.short_name";
        }

        return $this->db->get_res($sql);
    }

    /**
     * @Route(api-v3/api.php?action=HVFeedback)
     */
    public function HVFeedback(): array
    {
//        $this->post = json_decode('{"date_from":1627416001000,"date_to":1654027201000,"products":["SC-BL","BC"],"countries":null,"group_by":"platinum","aggregate":false}', true);
//        $this->post = json_decode('{"date_from":1654027200000,"date_to":1654545600000,"products":["SC-BL","BC"],"countries":null,"group_by":"platinum","aggregate":false}', true);

        $error["success"] = false;
        $error["message"] = "POST is empty.";
        $err = 0;
        $required = self::get_required('HVFeedback');
        foreach ($required as $v) {
            if (!isset($this->post[$v])) {
                $err = 1;
                $err_field[] = $v;
            }
            if ($err == 1) {
                $error["message"] = "Required fields: are missing: " . implode(",", $err_field);
            }
        }
        if ($err == 0) {
            $feedback_field = "dr_return_date";//test field. change when the present will be!!!
            $isAggregate = $this->post["aggregate"];
            $return_data = [];
            $values = [];

            $date_from = $this->post["date_from"] / 1000;
            $startDate = date("Y-m-d 00:00:01", $date_from);
            $date_from = strtotime($startDate);

            $date_to = $this->post["date_to"] / 1000;
            $endDate = date("Y-m-d 23:59:59", $date_to);
            $date_to = strtotime($endDate);


            $interval = (isset($this->post["interval_sec"])) ? $this->post["interval_sec"] : 3600;
            $matrix_data = self::getIntervalMatrix($date_from, $date_to, $interval);


            $countries = (!empty($this->post["countries"])) ? $this->post["countries"] : [];
            $country_data = $this->getCountryDataByShortnames($countries);

            foreach ($country_data as $country) {
                $country_id = $country["id"];
                $matrix_data["country_count"][$country_id] = $matrix_data["feedback_count"];
                $countryList[$country_id] = $country;
                $products = (!empty($this->post["products"])) ? $this->post["products"] : [];
                if ($isAggregate) {
                    $expected_product_ids = $this->getBusinessProductIds($products, $country_id);
                } else {
                    $expected_product_ids = $this->getProductIds($products, $country_id);
                }
                $product_ids = implode(",", $expected_product_ids);
                $periodData = $this->db->get_res("SELECT UNIX_TIMESTAMP(DATE_FORMAT(" . $feedback_field . ", '%Y-%m-%d %H:00:01')) AS ddate,COUNT(dr.drid) AS dcnt
                                                                     FROM `data_reviews` dr 
                                                                     WHERE UNIX_TIMESTAMP(" . $feedback_field . ") BETWEEN '$date_from' AND '$date_to'
                                                                       AND dr.dr_country_id = '$country_id'
                                                                       AND dr.dr_product_id IN ($product_ids)
                                                                       GROUP BY
                                                 			EXTRACT(YEAR FROM " . $feedback_field . "),
                                                 			EXTRACT(MONTH FROM " . $feedback_field . "),
                                                 			EXTRACT(DAY FROM " . $feedback_field . "),
                                                 			EXTRACT(HOUR FROM " . $feedback_field . ")
                                                 			");
                if ($periodData) {
                    foreach ($periodData as $v) {
                        $date = $v["ddate"];
                        $k = $date * 1000;
                        $cnt = $v["dcnt"];
                        $values[$country_id][$k] = $cnt;
                    }
                }
            }
            if ($values) {
                foreach ($matrix_data["country_count"] as $country_id => $v) {
                    if ($values[$country_id]) {
                        $totalcnt = 0;
                        foreach ($v as $time => $cnt) {
                            if (isset($values[$country_id][$time])) {
                                $totalcnt += $values[$country_id][$time];
                            }
                            $matrix_data["country_count"][$country_id][$time] = $totalcnt;
                        }
                        sort($matrix_data["country_count"][$country_id]);
                        $return_data['values'][] = [
                            'country' => $countryList[$country_id]['full_name'],
                            'country_shortname' => mb_strtoupper($countryList[$country_id]['short_name'], "UTF-8"),
                            'feedback_counts' => $matrix_data["country_count"][$country_id]
                        ];
                    }
                }
            }
            sort($matrix_data["dates"]);
            $return_data['dates'] = $matrix_data["dates"];
        }
        $return = (isset($return_data)) ? $return_data : $error;
        return $return;
    }

    public function HVProductsList() : array {
        $products_list = $this->db->get_res("
        SELECT 
            `bp`.`short_name` `shortname`,
            `bp`.`name` `name`,
            `bpp`.`short_name` `parent_shortname`,
            JSON_ARRAYAGG(UPPER(country.short_name)) `available_countries`
        FROM business_products bp
        INNER JOIN business_parent_products bpp ON bpp.id = bp.parent_product_id
        INNER JOIN products ON products.business_product_id = bp.id
        INNER JOIN country ON country.id = products.country_id
        GROUP BY bp.id
        ");

        foreach ($products_list as $product) {
            $products[] = [
                'shortname' => $product['shortname'],
                'name' => $product['name'],
                'parent_shortname' => $product['parent_shortname'],
                'available_countries' => json_decode($product['available_countries'])
            ];
        }

        header("Content-type: application/json; charset=utf-8");
        print json_encode($products);
        exit;
    }

    public function HVFeedbackType() : array {
        if (!isset($this->post['date_from']) || !isset($this->post['date_to'])) {
            $date_from = time() - 24 * 60 * 60;
            $date_to = time();
        } else {
            $date_from = microTimeToTime($this->post['date_from']);
            $date_to = microTimeToTime($this->post['date_to']);
        }

        $products = $this->post['products'];
        $countries = $this->post['countries'];
        $is_aggregate_by_parent = $this->post['aggregate'] == "true";

        $product_countries_string = $countries 
            ? "'" . implode("','", $countries) . "'"
            : null;
        $product_nicknames_string = $products 
            ? "'" . implode("','", $products) . "'"
            : null; 

        $listing_products_id = $is_aggregate_by_parent
            ? $this->getProductIdsByParent($product_nicknames_string, $product_countries_string)
            : $this->getProductIdsByCountryShortname($product_nicknames_string, $product_countries_string);
        $reviews_count = $this->getCountReviewsByType($listing_products_id, $date_from, $date_to);

        $formatted_count = [];
        $amazon_review_count = 0;
        $emails_review_count = 0;
        $ncx_review_count = 0;
        $fba_review_count = 0;

        foreach ($reviews_count as $group_review) {
            if (is_numeric($group_review['dr_type'])) {
                $amazon_review_count += (int)$group_review['count'];
            }
            if ($group_review['dr_type'] === 'email') {
                $emails_review_count += (int)$group_review['count'];
            }
            if ($group_review['dr_type'] === 'ncx' || $group_review['dr_type'] === 'voc') {
                $ncx_review_count += (int)$group_review['count'];
            }
            if ($group_review['dr_type'] === 'fba') {
                $fba_review_count += (int)$group_review['count'];
            }
        }

        $formatted_count = [
            'reviews' => $amazon_review_count,
            'emails' => $emails_review_count,
            'ncx' => $ncx_review_count,
            'fba_returns' => $fba_review_count
        ];

        return $formatted_count;
    }

    public function HVGroupingList() : array 
    {
        $tier_list = $this->db->get_res(
            "SELECT `name` groupName
             FROM business_tier"
        );

        return $tier_list;
    }

    public function HVOverallDates(): array
    {
        $dates = $this->db->get_res("
        SELECT 
            UNIX_TIMESTAMP(max(dr_date)) * 1000 date_to,
            UNIX_TIMESTAMP(min(dr_date)) * 1000 date_from
        FROM `data_reviews`
        WHERE UNIX_TIMESTAMP(dr_date) > 0
        ");

        foreach ($dates as $i => $date) {
            $dates[$i]['date_from'] = (int)$date['date_from'];
            $dates[$i]['date_to'] = (int)$date['date_to'];
        }

        $dates_object = [
            'date_from' => $dates[0]['date_from'],
            'date_to' => $dates[0]['date_to']
        ];

        return $dates_object;
    }

    private function getCountReviewsByType($list_products_id, $date_from, $date_to)
    {
        $products_ids_list_string = implode(', ', $list_products_id);
        $sql = "SELECT `dr_type`, 
                       COUNT(*) `count`
                FROM `data_reviews`
                WHERE `dr_product_id` IN ($products_ids_list_string)
                AND (UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to') 
                GROUP BY `dr_type`
        ";

        return $this->db->get_res($sql);
    }

    private function getCounterByCountryIds($date_from, $date_to, $country_ids, $product_nicknames): array
    {
        $country_id_counters_sql = "SELECT `dr_country_id` as `country_id`, COUNT(`dr_country_id`) as `count` FROM `data_reviews`
                    WHERE UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' AND";

        foreach ($country_ids as $country_id) {
            $country_id_counters_sql .= " (`dr_country_id` = '$country_id' AND dr_product_id IN(";
            $product_ids = $this->getProductIds($product_nicknames, $country_id);

            foreach ($product_ids as $product_id) {
                $country_id_counters_sql .= "'$product_id',";
            }
            $country_id_counters_sql = rtrim($country_id_counters_sql, ',');
            $country_id_counters_sql .= "))";
            $country_id_counters_sql .= " OR";
        }

        $country_id_counters_sql = rtrim($country_id_counters_sql, 'OR');
        $country_id_counters_sql .= " GROUP BY `dr_country_id`";

        $country_id_counters = $this->db->get_res($country_id_counters_sql);
        $countryCounterById = [];

        foreach ($country_id_counters as $counter) {
            $countryCounterById[$counter['country_id']] = $counter['count'];
        }

        $data = [];

        foreach ($country_ids as $country_id) {
            $data[$country_id] = $countryCounterById[$country_id];
        }

        return $data;
    }

    private function getCounterOtherCountries($date_from, $date_to, $country_ids, $product_nicknames): array
    {
        $other_country_ids_sql = "SELECT DISTINCT `dr_country_id` as `country_id`, COUNT(`dr_country_id`) as `count` FROM `data_reviews`
                    WHERE UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' AND (";

        foreach ($country_ids as $country_id) {
            $product_ids = $this->getProductIds($product_nicknames, $country_id);

            if (empty($product_ids)) {
                continue;
            }

            $other_country_ids_sql .= " (`dr_country_id` = '$country_id' AND dr_product_id IN(";

            foreach ($product_ids as $product_id) {
                $other_country_ids_sql .= "'$product_id',";
            }
            $other_country_ids_sql = rtrim($other_country_ids_sql, ',');
            $other_country_ids_sql .= "))";
            $other_country_ids_sql .= " OR";
        }


        $other_country_ids_sql = rtrim($other_country_ids_sql, 'OR');
        $other_country_ids_sql .= ")";
        $other_country_ids_sql .= " GROUP BY `dr_country_id`";

        $other_country_data = $this->db->get_res($other_country_ids_sql);

        $data = [];

        foreach ($other_country_data as $country_data) {
            $data["{$country_data['country_id']}"] = $country_data['count'];
        }

        return $data;
    }

    private function countOtherCountryIds($other_country_ids)
    {
        $counter = 0;

        foreach ($other_country_ids as $country_id) {
            $counter += $country_id['count'];
        }

        return $counter;
    }

    private function getCountryDataByShortnames($country_shortnames = [])
    {
        $sql = "SELECT `id`, `full_name`, `short_name` FROM `country` ";
        if (!empty($country_shortnames)) {
            $sql .= "WHERE";
            foreach ($country_shortnames as $shortname) {
                $sql .= " `short_name` = '$shortname' OR";
            }
            $sql = rtrim($sql, 'OR');
        }
        return $this->db->get_res($sql);
    }


    private function getReviewsDataByPeriod($date_from, $date_to, $country_id): array
    {
        $sql = "SELECT `drid` as `id`, UNIX_TIMESTAMP(`dr_date`) as `date` FROM `data_reviews` 
                    WHERE UNIX_TIMESTAMP(`dr_date`) BETWEEN '$date_from' AND '$date_to' AND `dr_country_id` = '$country_id' AND (";

        $expected_product_ids = $this->getProductIds($this->post['products'], $country_id);

        foreach ($expected_product_ids as $product_id) {
            $sql .= " `dr_product_id` = '$product_id' OR";
        }
        $sql = rtrim($sql, 'OR');
        $sql .= ");";

        $reviewsData = $this->db->get_res($sql);

        $data = [];

        foreach ($reviewsData as $review) {
            $data['date'][] = (int)$review['date'];
            $data['value'][] = (int)$review['id'];
        }

        return $data;
    }

    private function getBusinessProductIds($products = [], int $country_id): array
    {
        $product_nicknames = (!empty($products)) ? mb_strtolower(implode("','", $products), "UTF-8") : '';
        $parent_product_nicknames = self::getBusinessProductsByParentShortnames($product_nicknames);
        $sql = "SELECT id FROM products
                    WHERE business_product_id IN (SELECT id FROM business_products WHERE short_name IN ('$parent_product_nicknames'))
                      AND country_id = '$country_id'";
        return $this->db->fas_col($sql);
    }

    private function getIntervalMatrix($date_from, $date_to, $interval = 3600)
    {
        $countHoursBetweenDates = floor(($date_to - $date_from) / $interval);
        for ($i = 1; $i <= $countHoursBetweenDates; $i++) {
            $k = ($date_from + ($i * $interval)) * 1000;
            $matrix_data["dates"][$k] = $k;
            $matrix_data["feedback_count"][$k] = 0;
        }
        return $matrix_data;
    }

    private function getProductIdsByCountryShortname($product_nicknames, $product_countries) {
        if (empty($product_countries) && empty($product_nicknames)) {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    INNER JOIN products ON products.business_product_id = bp.id
                    INNER JOIN country ON country.id = products.country_id";
        }
        elseif (empty($product_countries) && !empty($product_nicknames)) {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    INNER JOIN products ON products.business_product_id = bp.id
                    INNER JOIN country ON country.id = products.country_id
                    WHERE bp.short_name IN ($product_nicknames)";
        }
        elseif (!empty($product_countries) && empty($product_nicknames)) {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    INNER JOIN products ON products.business_product_id = bp.id
                    INNER JOIN country ON country.id = products.country_id
                    WHERE country.short_name IN ($product_countries)";
        }
        else {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    INNER JOIN products ON products.business_product_id = bp.id
                    INNER JOIN country ON country.id = products.country_id
                    WHERE bp.short_name IN ($product_nicknames)
                    AND country.short_name IN ($product_countries)
                    ";
        }
        $sql .= " AND hide = 0";

        return array_column($this->db->get_res($sql), 'id');
    }

    private function getProductIdsByParent($product_nicknames, $product_countries): array
    {      
        if (empty($product_countries) && empty($product_nicknames)) {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bp.parent_product_id = bpp.id
                    LEFT JOIN products ON products.business_product_id = bp.id
                    LEFT JOIN country ON country.id = products.country_id";
        }
        elseif (empty($product_countries) && !empty($product_nicknames)) {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bp.parent_product_id = bpp.id
                    LEFT JOIN products ON products.business_product_id = bp.id
                    LEFT JOIN country ON country.id = products.country_id
                    WHERE bpp.short_name IN ($product_nicknames)";
        }
        elseif (!empty($product_countries) && empty($product_nicknames)) {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bp.parent_product_id = bpp.id
                    LEFT JOIN products ON products.business_product_id = bp.id
                    LEFT JOIN country ON country.id = products.country_id
                    WHERE country.short_name IN ($product_countries)";
        }
        else {
            $sql = "SELECT `products`.`id` 
                    FROM business_products bp
                    LEFT JOIN business_parent_products bpp ON bp.parent_product_id = bpp.id
                    LEFT JOIN products ON products.business_product_id = bp.id
                    LEFT JOIN country ON country.id = products.country_id
                    WHERE bpp.short_name IN ($product_nicknames)
                    AND country.short_name IN ($product_countries)
            ";
        }
        $sql .= " AND hide = 0";  

        return array_column($this->db->get_res($sql), 'id');
    }

    private function getProductIds($product_nicknames = [], int $country_id): array
    {
        $sql = "SELECT `id` FROM `products` WHERE `country_id` = '$country_id' ";
        if (!empty($product_nicknames)) {
            $sql .= "AND (";
            foreach ($product_nicknames as $nickname) {
                $sql .= " `nickname` = '$nickname' OR";
            }
            $sql = rtrim($sql, 'OR');
            $sql .= ");";
        }

        $data = $this->db->get_res($sql);

        if (empty($data)) {
            return [];
        }

        return array_column($data, 'id');
    }
}