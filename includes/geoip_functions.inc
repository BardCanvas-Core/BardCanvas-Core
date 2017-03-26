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
 * @return string city; region; country; isp
 */
function forge_geoip_location($ip = "", $skip_isp = false)
{
    if( ! function_exists("geoip_record_by_name") ) return "";
    
    if( empty($ip) ) $ip = get_remote_address();
    
    $record       = @geoip_record_by_name($ip);
    $country_name = $record["country_name"];
    $region_name  = @geoip_region_name_by_code($record["country_code"], $record["region"]);
    $city         = $record["city"];
    
    if( empty($city) )         $city = "n/a";
    if( empty($region_name) )  $region_name = "n/a";
    if( empty($country_name) ) $country_name = "n/a";
    
    
    if( ! $skip_isp )
    {
        $isp = @geoip_org_by_name($ip);
        if( empty($isp) ) $isp = "n/a";
        
        return utf8_encode("$city; $region_name; $country_name; $isp");
    }
    else
    {
        return utf8_encode("$city; $region_name; $country_name");
    }
}

/**
 * Returns explicit GeoIP info
 *
 * @param string $ip
 * @param string $what country_code, country_name, region_name, city, isp
 *
 * @returns string
 */
function get_geoip_location_data($ip, $what = "country_code")
{
    if( ! function_exists("geoip_record_by_name") ) return "";
    
    if( empty($ip) ) $ip = get_remote_address();
    
    $record       = @geoip_record_by_name($ip);
    $country_code = $record["country_code"];
    $country_name = $record["country_name"];
    $region_name  = @geoip_region_name_by_code($record["country_code"], $record["region"]);
    $city         = $record["city"];
    $isp          = @geoip_org_by_name($ip);
    
    if( empty($city) ) $city = "n/a";
    if( empty($region_name) ) $region_name = "n/a";
    if( empty($country_name) ) $country_name = "n/a";
    if( empty($isp) ) $isp = "n/a";
    
    if( $what == "country_code" ) return utf8_encode($country_code);
    if( $what == "country_name" ) return utf8_encode($country_name);
    if( $what == "region_name" )  return utf8_encode($region_name);
    if( $what == "city" )         return utf8_encode($city);
    if( $what == "isp" )          return utf8_encode($isp);
    
    return "";
}
