<?php

namespace app\helpers;
use Yii;

use DateTime;

class CustomHelper
{

    /* Key: Next prime greater than 62 ^ n / 1.618033988749894848 */
    /* Value: modular multiplicative inverse */
    private static $golden_primes = array(
        '1'                  => '1',
        '41'                 => '59',
        '2377'               => '1677',
        '147299'             => '187507',
        '9132313'            => '5952585',
        '566201239'          => '643566407',
        '35104476161'        => '22071637057',
        '2176477521929'      => '294289236153',
        '134941606358731'    => '88879354792675',
        '8366379594239857'   => '7275288500431249',
        '518715534842869223' => '280042546585394647'
    );

    /* Ascii :                    0  9,         A  Z,         a  z     */
    /* $chars = array_merge(range(48,57), range(65,90), range(97,122)) */
    private static $chars62 = array(
        0=>48,1=>49,2=>50,3=>51,4=>52,5=>53,6=>54,7=>55,8=>56,9=>57,10=>65,
        11=>66,12=>67,13=>68,14=>69,15=>70,16=>71,17=>72,18=>73,19=>74,20=>75,
        21=>76,22=>77,23=>78,24=>79,25=>80,26=>81,27=>82,28=>83,29=>84,30=>85,
        31=>86,32=>87,33=>88,34=>89,35=>90,36=>97,37=>98,38=>99,39=>100,40=>101,
        41=>102,42=>103,43=>104,44=>105,45=>106,46=>107,47=>108,48=>109,49=>110,
        50=>111,51=>112,52=>113,53=>114,54=>115,55=>116,56=>117,57=>118,58=>119,
        59=>120,60=>121,61=>122
    );

    public static function base62($int) {
        $key = "";
        while(bccomp($int, 0) > 0) {
            $mod = bcmod($int, 62);
            $key .= chr(self::$chars62[$mod]);
            $int = bcdiv($int, 62);
        }
        return strrev($key);
    }

    public static function hash($num, $len = 5) {
        $ceil = bcpow(62, $len);
        $primes = array_keys(self::$golden_primes);
        $prime = $primes[$len];
        $dec = bcmod(bcmul($num, $prime), $ceil);
        $hash = self::base62($dec);
        return str_pad($hash, $len, "0", STR_PAD_LEFT);
    }

    public static function unbase62($key) {
        $int = 0;
        foreach(str_split(strrev($key)) as $i => $char) {
            $dec = array_search(ord($char), self::$chars62);
            $int = bcadd(bcmul($dec, bcpow(62, $i)), $int);
        }
        return $int;
    }

    public static function unhash($hash) {
        $len = strlen($hash);
        $ceil = bcpow(62, $len);
        $mmiprimes = array_values(self::$golden_primes);
        $mmi = $mmiprimes[$len];
        $num = self::unbase62($hash);
        $dec = bcmod(bcmul($num, $mmi), $ceil);
        return $dec;
    }

    public static function encrypt($plaintext, $password) {
        $method = "AES-256-CBC";
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(16);
    
        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);
    
        return $iv . $hash . $ciphertext;
    }
    
    public static function decrypt($ivHashCiphertext, $password) {
        $method = "AES-256-CBC";
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash('sha256', $password, true);
    
        if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;
    
        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function toISODate($str)
    {

        if (empty($str)) return $str;

        date_default_timezone_set("Asia/Jakarta");

        $orig_date = new DateTime($str);
        return new DateTime($orig_date->getTimestamp() * 1000);
    }
    
    // Function to get the client IP address
    public static function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public static function get_IP_address()
    {
        foreach (array('HTTP_CLIENT_IP',
                    'HTTP_X_FORWARDED_FOR',
                    'HTTP_X_FORWARDED',
                    'HTTP_X_CLUSTER_CLIENT_IP',
                    'HTTP_FORWARDED_FOR',
                    'HTTP_FORWARDED',
                    'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $IPaddress){
                    $IPaddress = trim($IPaddress); // Just to be safe

                    if (filter_var($IPaddress,
                                FILTER_VALIDATE_IP,
                                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                        !== false) {

                        return $IPaddress;
                    }
                }
            }
        }
    }

    public static function getReviews() {

        
        $option = array(
            'googlemaps_free_apikey' => Yii::$app->params['googlemaps_free_apikey'],       
            'google_maps_review_cid' => Yii::$app->params['google_maps_review_cid'], 
            'google_reviews_sorting' => 'most_relevant',  // reviews are sorted by relevance (default), or in chronological order (most_relevant/newest)
            'cache_data_xdays_local' => 30,       // every x day the reviews are loaded from google (save API traffic)
            'your_language_for_tran' => 'id',     // give you language for auto translate reviews
            'show_not_more_than_max' => 10,        // (0-5) only show first x reviews
            'show_only_if_with_text' => true,    // true = show only reviews that have text
            'show_only_if_greater_x' => 0,        // (0-4) only show reviews with more than x stars
            'sort_reviews_by_a_data' => 'rating', // sort by 'time' or by 'rating' (newest/best first)
            'show_cname_as_headline' => true,     // true = show customer name as headline
            'show_stars_in_headline' => true,     // true = show customer stars after name in headline
            'show_author_avatar_img' => true,     // true = show the author avatar image (rounded)
            'show_blank_star_till_5' => true,     // false = don't show always 5 stars e.g. ⭐⭐⭐☆☆
            'show_txt_of_the_review' => true,     // true = show the text of each review
            'show_author_of_reviews' => true,     // true = show the author of each review
            'show_age_of_the_review' => true,     // true = show the age of each review
            'dateformat_for_the_age' => 'd-m-Y',  // see https://www.php.net/manual/en/datetime.format.php
            'show_rule_after_review' => true,     // false = don't show <hr> Tag after each review (and before first)
            'add_schemaorg_metadata' => true,     // add schemo.org data to loop back your rating to SERP
            'reviews_no_translations' => true,      
        );
        
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id='.$option['google_maps_review_cid'].'&reviews_sort='.$option['google_reviews_sorting'].'&key='.$option['googlemaps_free_apikey'];
        if (function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ( isset($option['your_language_for_tran']) and !empty($option['your_language_for_tran']) ) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Language: '.$option['your_language_for_tran']));
            }
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $arrContextOptions=array(
                'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                ),
                'http' => array(
                'method' => 'GET',
                'header' => 'Accept-language: '.$option['your_language_for_tran']."\r\n" .
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36\r\n"
                )
            );  
            $result = file_get_contents($url, false, stream_context_create($arrContextOptions));
        }
    
        $fp = fopen('reviews.json', 'w');
        fwrite($fp, $result);
        fclose($fp);
    
        $data  = json_decode($result, true);
        #echo'<pre>';var_dump($data);echo'</pre>'; // DEV & DEBUG
    
        $reviews = $data['result'];
        return $reviews;
        // $html = '';
    
        // if (!empty($reviews)) {
        //     if ( isset($option['sort_reviews_by_a_data']) and $option['sort_reviews_by_a_data'] == 'rating' ) {
        //         array_multisort(array_map(function($element) { return $element['rating']; }, $reviews), SORT_DESC, $reviews);
        //     } else if ( isset($option['sort_reviews_by_a_data']) and $option['sort_reviews_by_a_data'] == 'time' ) {
        //     array_multisort(array_map(function($element) { return $element['time']; }, $reviews), SORT_DESC, $reviews);
        //     }
        //     $html .= '<div class="review">';
        //     if (isset($option['show_cname_as_headline']) and $option['show_cname_as_headline'] == true) {
        //         $html .= '<strong>'.$data['result']['name'].' ';
        //         if (isset($option['show_stars_in_headline']) and $option['show_stars_in_headline'] == true) {
        //         for ($i=1; $i <= $data['result']['rating']; ++$i) $html .= '⭐';
        //         if (isset($option['show_blank_star_till_5']) and $option['show_blank_star_till_5'] == true) for ($i=1; $i <= 5-floor($data['result']['rating']); ++$i) $html .= '☆';
        //     }
        //     $html .= '</strong><br>';
        //     }
        //     if (isset($option['add_schemaorg_metadata']) and $option['add_schemaorg_metadata'] == true) {
        //         $html .= '<itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating"><meta itemprop="worstRating" content="1"/><meta itemprop="bestRating" content="5"/>';
        //         $html .= '<meta itemprop="ratingValue" content="'.$data['result']['rating'].'"/>';
        //     }
        //     if (isset($option['show_rule_after_review']) and $option['show_rule_after_review'] == true) $html .= '<hr size="1">';
        //     foreach ($reviews as $key => $review) {
        //     if (isset($option['show_not_more_than_max']) and $option['show_not_more_than_max'] > 0 and $key >= $option['show_not_more_than_max']) continue;
        //     if (isset($option['show_only_if_with_text']) and $option['show_only_if_with_text'] == true and empty($review['text'])) continue;
        //     if (isset($option['show_only_if_greater_x']) and $review['rating'] <= $option['show_only_if_greater_x']) continue;
        //     if (isset($option['show_author_of_reviews']) and $option['show_author_of_reviews'] == true and
        //         isset($option['show_author_avatar_img']) and $option['show_author_avatar_img'] == true) $html .= '<img class="avatar" src="'.$review['profile_photo_url'].'">';
        //     for ($i=1; $i <= $review['rating']; ++$i) $html .= '⭐';
        //     if (isset($option['show_blank_star_till_5']) and $option['show_blank_star_till_5'] == true) for ($i=1; $i <= 5-$review['rating']; ++$i) $html .= '☆';
        //     $html .= '<br>';
        //     if (isset($option['show_txt_of_the_review']) and $option['show_txt_of_the_review'] == true) $html .= str_replace(array("\r\n", "\r", "\n"), ' ', $review['text']).'<br>';
        //     if (isset($option['show_author_of_reviews']) and $option['show_author_of_reviews'] == true) $html .= '<small>'.$review['author_name'].' </small>';
        //     if (isset($option['show_age_of_the_review']) and $option['show_age_of_the_review'] == true) $html .= '<small> '.date($option['dateformat_for_the_age'], $review['time']).'  &mdash; '.$review['relative_time_description'].' </small>';
        //     if (isset($option['show_rule_after_review']) and $option['show_rule_after_review'] == true) $html .= '<hr style="clear:both" size="1">';
        //     }
        //     $html .= '</div>';
        // }
        
        // return $html;
    }

}
