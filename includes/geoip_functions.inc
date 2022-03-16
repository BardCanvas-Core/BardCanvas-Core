<?php
/**
 * Maxmind GeoIP functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

/**
 * Forging of country, state, city info
 *
 * @param string $ip
 * @param bool   $skip_isp
 *
 * @return string city; region; country[; isp]
 */
function forge_geoip_location($ip = "", $skip_isp = false)
{
    if( empty($ip) ) $ip = get_remote_address();
    
    $city = get_geoip_location_data($ip, "city");
    $region_name = get_geoip_location_data($ip, "region_name");
    $country_name = get_geoip_location_data($ip, "country_name");
    
    if( ! $skip_isp )
    {
        $isp = get_geoip_location_data($ip, "isp");
        return "$city; $region_name; $country_name; $isp";
    }
    
    return "$city; $region_name; $country_name";
}

/**
 * Returns city, region and country
 * 
 * @param string $ip
 *
 * @return string
 */
function get_geoip_location($ip = "")
{
    return forge_geoip_location($ip, true);
}

/**
 * Returns city, region, country and ISP
 * 
 * @param string $ip
 *
 * @return string
 */
function get_geoip_location_with_isp($ip = "")
{
    return forge_geoip_location($ip);
}

/**
 * Returns explicit GeoIP info
 *
 * @param string $ip
 * @param string $what = ["country_code", "country_name", "region", "region_name", "city", "isp"][@any]
 *
 * @returns string
 */
function get_geoip_location_data($ip, $what = "country_code")
{
    if( empty($ip) ) $ip = get_remote_address();
    
    if( defined("IPINFOIO_TOKEN") )
    {
        $record = __ipinfoio_get_all($ip);
        if( is_null($record) ) return "";
        
        $region       = $record["region"];
        $region_name  = $record["region_name"];
        $country_code = $record["country_code"];
        $isp          = $record["isp"];
    }
    else
    {
        if( ! function_exists("geoip_record_by_name") ) return "";
        
        $record       = @geoip_record_by_name($ip);
        $region       = $record["region"];
        $country_code = $record["country_code"];
        $region_name  = @geoip_region_name_by_code($country_code, $region);
        
        $isp = "";
        if( function_exists("geoip_isp_by_name") )     $isp = @geoip_isp_by_name($ip);
        elseif( function_exists("geoip_org_by_name") ) $isp = @geoip_org_by_name($ip);
    }
    
    $country_name = $record["country_name"];
    $city         = $record["city"];
    
    if( empty($city) )         $city         = "n/a";
    if( empty($region) )       $region       = "n/a";
    if( empty($region_name) )  $region_name  = "n/a";
    if( empty($country_name) ) $country_name = "n/a";
    if( empty($isp) )          $isp          = "n/a";
    
    if( $what == "country_code" ) return utf8_encode($country_code);
    if( $what == "country_name" ) return utf8_encode($country_name);
    if( $what == "region" )       return utf8_encode($region);
    if( $what == "region_name" )  return utf8_encode($region_name);
    if( $what == "city" )         return utf8_encode($city);
    if( $what == "isp" )          return utf8_encode($isp);
    
    return "";
}

/**
 * @param string $ip
 *
 * @return array|null
 */
function __ipinfoio_get_all($ip)
{
    if( ! defined("IPINFOIO_TOKEN") ) return null;
    
    $cache_dir = ROOTPATH . "/data/cache/ipinfo.io";
    if( ! is_dir($cache_dir) )
        if( ! @mkdir($cache_dir, 0777, true) )
            return null;
    
    $cache_file = "$cache_dir/$ip.dat";
    if( file_exists($cache_file) )
    {
        $filetime = filemtime($cache_file);
        if( $filetime > strtotime("now - 30 days") )
            return unserialize(file_get_contents($cache_file));
    }
    
    $log_date = date("Ymd");
    $log_time = date("H:i:s");
    $log_file = ROOTPATH . "/logs/ipinfo_io-$log_date.log";
    
    $token = IPINFOIO_TOKEN;
    $ch    = curl_init();
    curl_setopt($ch, CURLOPT_URL,            "https://ipinfo.io/$ip?token=$token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $contents = curl_exec($ch);
    
    if( curl_error($ch) )
    {
        $log_msg = sprintf("[$log_time] %s - Unable to get IPinfo.io data: %s\n\n", $ip, curl_error($ch));
        @file_put_contents($log_file, $log_msg, FILE_APPEND);
        return null;
    }
    
    curl_close($ch);
    
    if( empty($contents) )
    {
        $log_msg = sprintf("[$log_time] %s - Empty response received from IPinfo.io.\n\n", $ip);
        @file_put_contents($log_file, $log_msg, FILE_APPEND);
        return null;
    }
    
    $json = @json_decode($contents);
    if( empty($json) )
    {
        $log_msg = sprintf("[$log_time] %s - Data received from IPinfo.io is not JSON:\n\n", $ip)
                 . wordwrap($contents, 75, "\n           ") . "\n";
        @file_put_contents($log_file, $log_msg, FILE_APPEND);
        return null;
    }
    
    $json = (array) $json;
    
    $isp = $json["org"];
    if( preg_match("/^AS[0-9]+ (.*)/", $isp, $matches) ) $isp = $matches[1];
    
    $return = array(
        "country_code" => $json["country"],
        "country_name" => __ipinfoio_get_country_name($json["country"]),
        "region"       => $json["region"],
        "region_name"  => $json["region"],
        "city"         => $json["city"],
        "isp"          => $isp,
    );
    
    @file_put_contents($cache_file, serialize($return));
    
    $log_msg = sprintf(
        "[$log_time] %s - %s; %s; %s; %s\n",
        $ip, $return["city"], $return["region_name"], $return["country_name"], $return["isp"]
    );
    $log_msg .= "           Data saved to cache file.\n\n";
    @file_put_contents($log_file, $log_msg, FILE_APPEND);
    
    return $return;
}

/**
 * @param string $code
 * @see https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes/blob/master/all/all.csv
 *
 * @return string
 */
function __ipinfoio_get_country_name($code)
{
    $code = strtoupper($code);
    
    if($code == "AD") return "Andorra";
    if($code == "AE") return "United Arab Emirates";
    if($code == "AF") return "Afghanistan";
    if($code == "AG") return "Antigua and Barbuda";
    if($code == "AI") return "Anguilla";
    if($code == "AL") return "Albania";
    if($code == "AM") return "Armenia";
    if($code == "AO") return "Angola";
    if($code == "AQ") return "Antarctica";
    if($code == "AR") return "Argentina";
    if($code == "AS") return "American Samoa";
    if($code == "AT") return "Austria";
    if($code == "AU") return "Australia";
    if($code == "AW") return "Aruba";
    if($code == "AX") return "Åland Islands";
    if($code == "AZ") return "Azerbaijan";
    if($code == "BA") return "Bosnia and Herzegovina";
    if($code == "BB") return "Barbados";
    if($code == "BD") return "Bangladesh";
    if($code == "BE") return "Belgium";
    if($code == "BF") return "Burkina Faso";
    if($code == "BG") return "Bulgaria";
    if($code == "BH") return "Bahrain";
    if($code == "BI") return "Burundi";
    if($code == "BJ") return "Benin";
    if($code == "BL") return "Saint Barthélemy";
    if($code == "BM") return "Bermuda";
    if($code == "BN") return "Brunei Darussalam";
    if($code == "BO") return "Bolivia (Plurinational State of)";
    if($code == "BQ") return "Bonaire, Sint Eustatius and Saba";
    if($code == "BR") return "Brazil";
    if($code == "BS") return "Bahamas";
    if($code == "BT") return "Bhutan";
    if($code == "BV") return "Bouvet Island";
    if($code == "BW") return "Botswana";
    if($code == "BY") return "Belarus";
    if($code == "BZ") return "Belize";
    if($code == "CA") return "Canada";
    if($code == "CC") return "Cocos (Keeling) Islands";
    if($code == "CD") return "Congo, Democratic Republic of the";
    if($code == "CF") return "Central African Republic";
    if($code == "CG") return "Congo";
    if($code == "CH") return "Switzerland";
    if($code == "CI") return "Côte d'Ivoire";
    if($code == "CK") return "Cook Islands";
    if($code == "CL") return "Chile";
    if($code == "CM") return "Cameroon";
    if($code == "CN") return "China";
    if($code == "CO") return "Colombia";
    if($code == "CR") return "Costa Rica";
    if($code == "CU") return "Cuba";
    if($code == "CV") return "Cabo Verde";
    if($code == "CW") return "Curaçao";
    if($code == "CX") return "Christmas Island";
    if($code == "CY") return "Cyprus";
    if($code == "CZ") return "Czechia";
    if($code == "DE") return "Germany";
    if($code == "DJ") return "Djibouti";
    if($code == "DK") return "Denmark";
    if($code == "DM") return "Dominica";
    if($code == "DO") return "Dominican Republic";
    if($code == "DZ") return "Algeria";
    if($code == "EC") return "Ecuador";
    if($code == "EE") return "Estonia";
    if($code == "EG") return "Egypt";
    if($code == "EH") return "Western Sahara";
    if($code == "ER") return "Eritrea";
    if($code == "ES") return "Spain";
    if($code == "ET") return "Ethiopia";
    if($code == "FI") return "Finland";
    if($code == "FJ") return "Fiji";
    if($code == "FK") return "Falkland Islands (Malvinas)";
    if($code == "FM") return "Micronesia (Federated States of)";
    if($code == "FO") return "Faroe Islands";
    if($code == "FR") return "France";
    if($code == "GA") return "Gabon";
    if($code == "GB") return "United Kingdom of Great Britain and Northern Ireland";
    if($code == "GD") return "Grenada";
    if($code == "GE") return "Georgia";
    if($code == "GF") return "French Guiana";
    if($code == "GG") return "Guernsey";
    if($code == "GH") return "Ghana";
    if($code == "GI") return "Gibraltar";
    if($code == "GL") return "Greenland";
    if($code == "GM") return "Gambia";
    if($code == "GN") return "Guinea";
    if($code == "GP") return "Guadeloupe";
    if($code == "GQ") return "Equatorial Guinea";
    if($code == "GR") return "Greece";
    if($code == "GS") return "South Georgia and the South Sandwich Islands";
    if($code == "GT") return "Guatemala";
    if($code == "GU") return "Guam";
    if($code == "GW") return "Guinea-Bissau";
    if($code == "GY") return "Guyana";
    if($code == "HK") return "Hong Kong";
    if($code == "HM") return "Heard Island and McDonald Islands";
    if($code == "HN") return "Honduras";
    if($code == "HR") return "Croatia";
    if($code == "HT") return "Haiti";
    if($code == "HU") return "Hungary";
    if($code == "ID") return "Indonesia";
    if($code == "IE") return "Ireland";
    if($code == "IL") return "Israel";
    if($code == "IM") return "Isle of Man";
    if($code == "IN") return "India";
    if($code == "IO") return "British Indian Ocean Territory";
    if($code == "IQ") return "Iraq";
    if($code == "IR") return "Iran (Islamic Republic of)";
    if($code == "IS") return "Iceland";
    if($code == "IT") return "Italy";
    if($code == "JE") return "Jersey";
    if($code == "JM") return "Jamaica";
    if($code == "JO") return "Jordan";
    if($code == "JP") return "Japan";
    if($code == "KE") return "Kenya";
    if($code == "KG") return "Kyrgyzstan";
    if($code == "KH") return "Cambodia";
    if($code == "KI") return "Kiribati";
    if($code == "KM") return "Comoros";
    if($code == "KN") return "Saint Kitts and Nevis";
    if($code == "KP") return "Korea (Democratic People's Republic of)";
    if($code == "KR") return "Korea, Republic of";
    if($code == "KW") return "Kuwait";
    if($code == "KY") return "Cayman Islands";
    if($code == "KZ") return "Kazakhstan";
    if($code == "LA") return "Lao People's Democratic Republic";
    if($code == "LB") return "Lebanon";
    if($code == "LC") return "Saint Lucia";
    if($code == "LI") return "Liechtenstein";
    if($code == "LK") return "Sri Lanka";
    if($code == "LR") return "Liberia";
    if($code == "LS") return "Lesotho";
    if($code == "LT") return "Lithuania";
    if($code == "LU") return "Luxembourg";
    if($code == "LV") return "Latvia";
    if($code == "LY") return "Libya";
    if($code == "MA") return "Morocco";
    if($code == "MC") return "Monaco";
    if($code == "MD") return "Moldova, Republic of";
    if($code == "ME") return "Montenegro";
    if($code == "MF") return "Saint Martin (French part)";
    if($code == "MG") return "Madagascar";
    if($code == "MH") return "Marshall Islands";
    if($code == "MK") return "North Macedonia";
    if($code == "ML") return "Mali";
    if($code == "MM") return "Myanmar";
    if($code == "MN") return "Mongolia";
    if($code == "MO") return "Macao";
    if($code == "MP") return "Northern Mariana Islands";
    if($code == "MQ") return "Martinique";
    if($code == "MR") return "Mauritania";
    if($code == "MS") return "Montserrat";
    if($code == "MT") return "Malta";
    if($code == "MU") return "Mauritius";
    if($code == "MV") return "Maldives";
    if($code == "MW") return "Malawi";
    if($code == "MX") return "Mexico";
    if($code == "MY") return "Malaysia";
    if($code == "MZ") return "Mozambique";
    if($code == "NA") return "Namibia";
    if($code == "NC") return "New Caledonia";
    if($code == "NE") return "Niger";
    if($code == "NF") return "Norfolk Island";
    if($code == "NG") return "Nigeria";
    if($code == "NI") return "Nicaragua";
    if($code == "NL") return "Netherlands";
    if($code == "NO") return "Norway";
    if($code == "NP") return "Nepal";
    if($code == "NR") return "Nauru";
    if($code == "NU") return "Niue";
    if($code == "NZ") return "New Zealand";
    if($code == "OM") return "Oman";
    if($code == "PA") return "Panama";
    if($code == "PE") return "Peru";
    if($code == "PF") return "French Polynesia";
    if($code == "PG") return "Papua New Guinea";
    if($code == "PH") return "Philippines";
    if($code == "PK") return "Pakistan";
    if($code == "PL") return "Poland";
    if($code == "PM") return "Saint Pierre and Miquelon";
    if($code == "PN") return "Pitcairn";
    if($code == "PR") return "Puerto Rico";
    if($code == "PS") return "Palestine, State of";
    if($code == "PT") return "Portugal";
    if($code == "PW") return "Palau";
    if($code == "PY") return "Paraguay";
    if($code == "QA") return "Qatar";
    if($code == "RE") return "Réunion";
    if($code == "RO") return "Romania";
    if($code == "RS") return "Serbia";
    if($code == "RU") return "Russian Federation";
    if($code == "RW") return "Rwanda";
    if($code == "SA") return "Saudi Arabia";
    if($code == "SB") return "Solomon Islands";
    if($code == "SC") return "Seychelles";
    if($code == "SD") return "Sudan";
    if($code == "SE") return "Sweden";
    if($code == "SG") return "Singapore";
    if($code == "SH") return "Saint Helena, Ascension and Tristan da Cunha";
    if($code == "SI") return "Slovenia";
    if($code == "SJ") return "Svalbard and Jan Mayen";
    if($code == "SK") return "Slovakia";
    if($code == "SL") return "Sierra Leone";
    if($code == "SM") return "San Marino";
    if($code == "SN") return "Senegal";
    if($code == "SO") return "Somalia";
    if($code == "SR") return "Suriname";
    if($code == "SS") return "South Sudan";
    if($code == "ST") return "Sao Tome and Principe";
    if($code == "SV") return "El Salvador";
    if($code == "SX") return "Sint Maarten (Dutch part)";
    if($code == "SY") return "Syrian Arab Republic";
    if($code == "SZ") return "Eswatini";
    if($code == "TC") return "Turks and Caicos Islands";
    if($code == "TD") return "Chad";
    if($code == "TF") return "French Southern Territories";
    if($code == "TG") return "Togo";
    if($code == "TH") return "Thailand";
    if($code == "TJ") return "Tajikistan";
    if($code == "TK") return "Tokelau";
    if($code == "TL") return "Timor-Leste";
    if($code == "TM") return "Turkmenistan";
    if($code == "TN") return "Tunisia";
    if($code == "TO") return "Tonga";
    if($code == "TR") return "Turkey";
    if($code == "TT") return "Trinidad and Tobago";
    if($code == "TV") return "Tuvalu";
    if($code == "TW") return "Taiwan, Province of China";
    if($code == "TZ") return "Tanzania, United Republic of";
    if($code == "UA") return "Ukraine";
    if($code == "UG") return "Uganda";
    if($code == "UM") return "United States Minor Outlying Islands";
    if($code == "US") return "United States of America";
    if($code == "UY") return "Uruguay";
    if($code == "UZ") return "Uzbekistan";
    if($code == "VA") return "Holy See";
    if($code == "VC") return "Saint Vincent and the Grenadines";
    if($code == "VE") return "Venezuela (Bolivarian Republic of)";
    if($code == "VG") return "Virgin Islands (British)";
    if($code == "VI") return "Virgin Islands (U.S.)";
    if($code == "VN") return "Viet Nam";
    if($code == "VU") return "Vanuatu";
    if($code == "WF") return "Wallis and Futuna";
    if($code == "WS") return "Samoa";
    if($code == "YE") return "Yemen";
    if($code == "YT") return "Mayotte";
    if($code == "ZA") return "South Africa";
    if($code == "ZM") return "Zambia";
    if($code == "ZW") return "Zimbabwe";
    
    return "n/a";
}

/**
 * Returns location information that can be safely disclosed to users
 * 
 * @param string $ip
 *
 * @return string
 */
function get_geoip_disclosable_location($ip = "")
{
    return get_geoip_location_data($ip, "country_name");
}

/**
 * Returns the given IP ISP (use only on undisclosable areas)
 * 
 * @param string $ip
 *
 * @return string
 */
function get_geoip_isp($ip = "")
{
    return get_geoip_location_data($ip, "isp");
}

/**
 * Returns the country code
 * 
 * @param string $ip
 *
 * @return string
 */
function get_geoip_country_code($ip = "")
{
    return get_geoip_location_data($ip, "country_code");
}

/**
 * Returns the country name
 * 
 * @param string $ip
 *
 * @return string
 */
function get_geoip_country_name($ip = "")
{
    return get_geoip_location_data($ip, "country_name");
}

function get_geoip_city($ip = "")
{
    return get_geoip_location_data($ip, "city");
}

function get_geoip_region($ip = "")
{
    return get_geoip_location_data($ip, "region");
}

function get_geoip_region_name($ip = "")
{
    return get_geoip_location_data($ip, "region_name");
}
