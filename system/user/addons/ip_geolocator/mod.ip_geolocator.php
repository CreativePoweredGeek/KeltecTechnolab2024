<?php

/**
 * 
 * IP Country
 * 
 * @package     IP Geo Locator
 * @author      Anthony Mellor <anthonymellor@climbingturn.co.uk>
 * @version     2.1.0
 * @since       1.0.0
 * @copyright   Copyright (c)2019 Climbing Turn Ltd
 * @link        https://www.climbingturn.co.uk
 * @see         This product includes GeoLite data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a>.
 * 
 */
class Ip_geolocator {

   
    /**
     * 
     * The geoPlugin lookup address
     * 
     * @var string
     * 
     */
    private $host = "http://www.geoplugin.net/php.gp?ip={IP}&base_currency={CURRENCY}&lang={LANG}";
        
    

    /**
     * 
     * The geoPlugin "nearby" API call
     * 
     * @var string
     * 
     */
    private $nearbyHost = "http://www.geoplugin.net/extras/nearby.gp?lat={LAT}&long={LNG}&radius={RADIUS}&limit={LIMIT}";



    /**
     * 
     * The default currency
     * 
     * @var string
     * 
     */
    private $defaultCurrency = 'GBP';
    
    

    /**
     * 
     * The default language code
     * 
     * @var string
     * 
     */
    private $defaultLanguage = 'en';



    /**
     * 
     * Default country code if all else fails.
     * 
     * @var string
     * 
     */
    private $defaultCountryCode = 'GB';


    /**
     * 
     * Default continent code if all else fails.
     * 
     * @var string
     * 
     */
    private $defaultContinentCode = 'EU';



    /**
     * 
     * @var string
     * 
     */
    private $sessionCacheKey = 'geodata';




    /**
     * 
     * The radius for calls to "nearby"
     * 
     * @var int
     * 
     */
    private $defaultRadius = 10;



    /**
     * 
     * The number of "nearby" results to return
     * 
     * @var int
     * 
     */
    private $defaultNearbyLimit = 10;




    /**
     * 
     * What to do on error - possible values are "fatal_error", "no_results" or "silent"
     * A value of "no_results" will output ee()->TMPL->no_results()
     * This can be overriden by using error_action="" in one of the tags
     * 
     * @var string
     * 
     */
    private $defaultErrorAction = 'fatal_error';


    /**
     * 
     * Can be overridden by setting the tag paramater detect_bots="yes|no"
     * Triggers bot detection and carries out the action specified by $botAction
     * 
     * @var bool
     * 
     */
    private $detectBots = true;


    /**
     * 
     * If $detectBots == TRUE then this will determine what action to take
     * Can be overridden by using the tag paramater bot_action=""
     * Values can be: 'silent' or 'error'. If "error" then you
     * can specify the error message using the parameter
     * bot_error_message="" (see below). 
     * NB: The error parameter will trigger an EE fatal error
     * 
     * @var string
     * 
     */
    private $botAction = 'silent';


    /**
     * 
     * The error message to display if a $detectBots == TRUE and a 
     * bot is detected and $botAction == 'error'
     * Overriden by the template parameter bot_error_message=""
     * 
     * @var string
     * 
     */
    private $botDetectedErrorMessage = "No Bots allowed";

    /**
     * 
     * Returns the country code
     * 
     * @return string
     * 
     */
    public function country_code()
    {
        $response = $this->lookup();

        if($response !== null && $response['geoplugin_countryCode'] !== null) {
            return $response['geoplugin_countryCode'];
        }

        return ee()->TMPL->fetch_param('default', $this->defaultCountryCode);
    }



    /**
     * 
     * Checks to see if the user's country code is included in a supplied
     * list of "allowed" country codes using the parameter allowed="XX|XX|XX"
     * If the country code cannot be determined then the default country
     * code is used either from the default="" parameter if it's supplied
     * or the default set by this add-on
     * 
     * @return bool
     * 
     */
    public function is_allowed()
    {
        $response = $this->lookup();

        if($response !== null && $response['geoplugin_countryCode'] !== null) {
            $countryCode = $response['geoplugin_countryCode'];
        } else {
            $countryCode = ee()->TMPL->fetch_param('default', $this->defaultCountryCode);
        }

        $allowed = ee()->TMPL->fetch_param('allowed', '');

        if($allowed === '') {
            return $this->error_action("ip_geolocator::is_allowed - Allowed country codes parameter is missing or empty");
        }

        $allowedArray = explode('|', $allowed);
        if(in_array($countryCode, $allowedArray)) {
            return true;        
        }

        return false;
    }



    /**
     * 
     * Returns the continent code
     * 
     * @return string
     * 
     */
    public function continent_code()
    {
        $response = $this->lookup();

        if($response !== null && $response['geoplugin_continentCode'] !== null) {
            return $response['geoplugin_continentCode'];
        }

        return ee()->TMPL->fetch_param('default', $this->defaultContinentCode);
    }



    /**
     * 
     * Looks up a specific IP address
     * 
     * @return template
     * 
     */
    public function ip_lookup()
    {
        $ip = ee()->TMPL->fetch_param('ip', null);
        if($ip === null) {
            return $this->error_action(__CLASS__ . ': ip_lookup, the ip parameter is missing');
        }

        $vars[0] = $this->get_geo_data($ip);

        if($vars[0] !== null) {
            return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
        }

        return ee()->TMPL->no_results();
    }



    /**
     * 
     * Returns a specific GEO Data variable
     * 
     * @return string
     * 
     */
    public function get_variable()
    {
        $response = $this->get_geo_data();

        if($response === null) {
            return '';
        }
       
        $variable = ee()->TMPL->fetch_param('name', false);

        if($variable === false) {
            return $this->error_action(__CLASS__ . ': The name parameter is missing');
        }

        if( ! isset($response[$variable])) {
            return $this->error_action(__CLASS__ . ': The name variable does not exist');
        }

        if($response[$variable] === null) {
            return '?';
        }

        return $response[$variable];
    }



    /**
     * 
     * Tag pair to return all the GEO Data
     * 
     * @return template
     * 
     */
    public function geo_data()
    {
        $vars[0] = $this->get_geo_data();

        if($vars[0] !== null) {
            return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
        }

        return ee()->TMPL->no_results();
    }


    /**
     * 
     * Find nearby places to the user
     * 
     * @return template
     * 
     */
    public function near_to_user()
    {
        $geoData = $this->get_geo_data();

        if($geoData === null) {
            return ee()->TMPL->no_results();    
        }

        if( count($geoData) > 0 && (! is_numeric($geoData['latitude']) || ! is_numeric($geoData['longitude']))) {
            return ee()->TMPL->no_results();
        }

        $data = $this->get_nearby($geoData['latitude'], $geoData['longitude']);

        if($data !== null) {
            return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data);
        }

        return ee()->TMPL->no_results();
    }




    /**
     * 
     * Find nearby places to user supplied lat and lng
     * 
     * @return template
     * 
     */
    public function near_to_lat_lng()
    {
        $lat = ee()->TMPL->fetch_param('lat', false);
        $lng = ee()->TMPL->fetch_param('lng', false);

        if($lat === false || $lng === false) {
            return $this->error_action(__CLASS__ . 'Latitude or Longitude are missing');
        }

        if( ! is_numeric($lat) || ! is_numeric($lng)) {
            return $this->error_action('Latitude and Longitude must be numeric');
        }

        $data = $this->get_nearby($lat, $lng);

        if($data !== null) {
            return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data);
        }

        return ee()->TMPL->no_results();
    }



    /**
     * 
     * Get nearby places to the supplied lat and lng
     * 
     * @param $lat
     * @param $lng
     * 
     * @return array | null
     * 
     */
    private function get_nearby($lat, $lng)
    {
        $radius = (int)ee()->TMPL->fetch_param('radius', $this->defaultRadius);
        $limit = (int)ee()->TMPL->fetch_param('limit', $this->defaultNearbyLimit);

        $host = str_replace('{LAT}', $lat, $this->nearbyHost);
        $host = str_replace('{LNG}', $lng, $host);
        $host = str_replace('{RADIUS}', $radius, $host);
        $host = str_replace('{LIMIT}', $limit, $host);

        $results = $this->apiCall($host);

        if($results === null) {
            return null;
        }

        if( ! isset($results[0]['geoplugin_place'])) {
            return null;
        }

        $data = [];

        foreach($results as $key => $result) {
            $data[] = ['place' => $result['geoplugin_place'],
                       'country_code' => $result['geoplugin_countryCode'],
                       'region' => $result['geoplugin_region'],
                       'latitude' => $result['geoplugin_latitude'],
                       'longitude' => $result['geoplugin_longitude'],
                       'distance_miles' => $result['geoplugin_distanceMiles'],
                       'distance_kilometers' => $result['geoplugin_distanceKilometers']
                   ];
        }

        return $data;
    }



    /**
     * 
     * Returns the GEO Data as an array
     * 
     * @param string $ip Optional
     * 
     * @return array | null
     * 
     */
    private function get_geo_data($ip = null)
    {
        $response = $this->lookup($ip);

        if($response === null ) {
            return null;
        }

        $vars['ip'] = $response['ip'];
        $vars['city'] = $response['geoplugin_city'];
        $vars['region'] = $response['geoplugin_region'];
        $vars['region_code'] = $response['geoplugin_regionCode'];
        $vars['region_name'] = $response['geoplugin_regionName'];
        $vars['dma_code'] = $response['geoplugin_dmaCode'];
        $vars['country_code'] = $response['geoplugin_countryCode'];
        $vars['country_name'] = $response['geoplugin_countryName'];
        $vars['in_eu'] = $response['geoplugin_inEU'];
        $vars['eu_vat_rate'] = $response['geoplugin_euVATrate'];
        $vars['continent_code'] = $response['geoplugin_continentCode'];
        $vars['continent_name'] = $response['geoplugin_continentName'];
        $vars['latitude'] = $response['geoplugin_latitude'];
        $vars['longitude'] = $response['geoplugin_longitude'];
        $vars['location_accuracy_radius'] = $response['geoplugin_locationAccuracyRadius'];
        $vars['timezone'] = $response['geoplugin_timezone'];
        $vars['currency_code'] = $response['geoplugin_currencyCode'];
        $vars['currency_symbol'] = $response['geoplugin_currencySymbol'];
        $vars['currency_converter'] = $response['geoplugin_currencyConverter'];

        if($vars['country_code'] === null) {
            $vars['country_code'] = ee()->TMPL->fetch_param('default_country', $this->defaultCountryCode);
        }

        if($vars['continent_code'] === null) {
            $vars['continent_code'] = ee()->TMPL->fetch_param('default_continent', $this->defaultContinentCode);
        }

        return $vars;
    }



    /**
     * 
     * Performs the lookup
     * 
     * @param string $ip Optional
     * 
     * @return array | null
     * 
     */
    private function lookup($ip = null)
    {
        $this->getCommonParameters();

        if($this->detectBots) {
            if($this->botDetected()) {
                return $this->botDetectedResponse();
            }
        }        

        if($ip === null) {
            $data = $this->getFromCache();
            if( ! empty($data)) {
                return $data;
            }
        }

        $currency = ee()->TMPL->fetch_param('currency', $this->defaultCurrency);
        $language = ee()->TMPL->fetch_param('language', $this->defaultLanguage);

        if($ip === null) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $host = str_replace('{IP}', $ip, $this->host);
        $host = str_replace('{CURRENCY}', $currency, $host);
        $host = str_replace('{LANG}', $language, $host);

        $data = $this->apiCall($host);

        if(is_array($data)) {
            $data['ip'] = $ip;
            ee()->session->set_cache(__CLASS__, $this->sessionCacheKey, $data);
            return $data;
        }

        return null;
    }



    /**
     * 
     * Makes an API call to the specified address
     * 
     * @param string $host The URI to call
     * 
     * @return array | null
     * 
     */
    private function apiCall(string $host)
    {
        if(function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ExpressionEngine IP Country v1.0');
            $response = curl_exec($ch);
            curl_close ($ch);
        } else if(ini_get('allow_url_fopen')) {
            $response = file_get_contents($host, 'r');
        } else {
            $this->error_action('IP Geo Locator fatal error: Either compile PHP with cURL support or enable allow_url_fopen in php.ini');
            return null;
        }
        
        return unserialize($response);
    }


    /**
     * 
     * Store any common EE tag parameters
     * 
     */
    private function getCommonParameters()
    {
        $default = $this->detectBots === true ? "yes" : "no";
        $detectBots = strtolower(ee()->TMPL->fetch_param('detect_bots', $default));
        if($detectBots === "no") {
            $this->detectBots = false;
        } else {
            $this->detectBots = true;
        }

        $this->botAction = ee()->TMPL->fetch_param('bot_action', $this->botAction);
        $this->botDetectedErrorMessage = ee()->TMPL->fetch_param('bot_action', $this->botDetectedErrorMessage);
    }



    /**
     * 
     * Attempt to retrieve the geo data from the session cache
     * 
     * @return array
     * 
     */
    private function getFromCache()
    {

        if(! ee()->session->cache(__CLASS__, $this->sessionCacheKey)) {
            return [];
        }

        return ee()->session->cache(__CLASS__, $this->sessionCacheKey);
    }



    /**
     * 
     * Checks the failure_action and displays a fatal error, no results or just dies
     * 
     * @param string $errorMessage
     * 
     */
    private function error_action(string $errorMessage)
    {
        $errorAction = ee()->TMPL->fetch_param('error_action', $this->defaultErrorAction);

        switch($errorAction) {
            case 'silent':
                return '';
            case 'no_results':
                return '';
            default:
                ee()->output->fatal_error($errorMessage);
                die();
        }
    }


    /**
     * 
     * If $detectBots is true then check for a bot
     * 
     * @return bool
     * 
     */
    private function botDetected()
    {
        $botRegexPattern = "(googlebot\/|Googlebot-Mobile|Googlebot-Image|Google favicon|Mediapartners-Google|bingbot|slurp|java|wget|curl|Commons-HttpClient|Python-urllib|libwww|httpunit|nutch|phpcrawl|msnbot|jyxobot|FAST-WebCrawler|FAST Enterprise Crawler|biglotron|teoma|convera|seekbot|gigablast|exabot|ngbot|ia_archiver|GingerCrawler|webmon |httrack|webcrawler|grub.org|UsineNouvelleCrawler|antibot|netresearchserver|speedy|fluffy|bibnum.bnf|findlink|msrbot|panscient|yacybot|AISearchBot|IOI|ips-agent|tagoobot|MJ12bot|dotbot|woriobot|yanga|buzzbot|mlbot|yandexbot|purebot|Linguee Bot|Voyager|CyberPatrol|voilabot|baiduspider|citeseerxbot|spbot|twengabot|postrank|turnitinbot|scribdbot|page2rss|sitebot|linkdex|Adidxbot|blekkobot|ezooms|dotbot|Mail.RU_Bot|discobot|heritrix|findthatfile|europarchive.org|NerdByNature.Bot|sistrix crawler|ahrefsbot|Aboundex|domaincrawler|wbsearchbot|summify|ccbot|edisterbot|seznambot|ec2linkfinder|gslfbot|aihitbot|intelium_bot|facebookexternalhit|yeti|RetrevoPageAnalyzer|lb-spider|sogou|lssbot|careerbot|wotbox|wocbot|ichiro|DuckDuckBot|lssrocketcrawler|drupact|webcompanycrawler|acoonbot|openindexspider|gnam gnam spider|web-archive-net.com.bot|backlinkcrawler|coccoc|integromedb|content crawler spider|toplistbot|seokicks-robot|it2media-domain-crawler|ip-web-crawler.com|siteexplorer.info|elisabot|proximic|changedetection|blexbot|arabot|WeSEE:Search|niki-bot|CrystalSemanticsBot|rogerbot|360Spider|psbot|InterfaxScanBot|Lipperhey SEO Service|CC Metadata Scaper|g00g1e.net|GrapeshotCrawler|urlappendbot|brainobot|fr-crawler|binlar|SimpleCrawler|Livelapbot|Twitterbot|cXensebot|smtbot|bnf.fr_bot|A6-Indexer|ADmantX|Facebot|Twitterbot|OrangeBot|memorybot|AdvBot|MegaIndex|SemanticScholarBot|ltx71|nerdybot|xovibot|BUbiNG|Qwantify|archive.org_bot|Applebot|TweetmemeBot|crawler4j|findxbot|SemrushBot|yoozBot|lipperhey|y!j-asr|Domain Re-Animator Bot|AddThis|YisouSpider|BLEXBot|YandexBot|SurdotlyBot|AwarioRssBot|FeedlyBot|Barkrowler|Gluten Free Crawler|Cliqzbot)";
        $userAgent = ee()->input->server('HTTP_USER_AGENT', TRUE);

        return preg_match("/{$botRegexPattern}/", $userAgent);
    }


    /**
     * 
     * Action to take of a bot is identified
     * 
     */
    private function botDetectedResponse()
    {
        switch($this->botAction) {                
            case 'error':
                ee()->output->fatal_error($this->botDetectedErrorMessage);
                die();
            default:
                return null;
        }
    }

}