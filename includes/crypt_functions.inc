<?php
/**
 * Cryptographic functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

/**
 * RIJNDAEL_256 decrypter
 *
 * @requires mcrypt package
 *
 * @param string $string_to_decrypt
 * @param string $key
 *
 * @returns mixed
 */
function decrypt($string_to_decrypt, $key)
{
    $string_to_decrypt = base64_decode($string_to_decrypt);
    $md5_key           = md5($key);
    $iv                = md5($md5_key);
    $rtn               = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_decrypt, MCRYPT_MODE_CBC, $iv);
    $rtn               = rtrim($rtn, "\0\4");
    
    return ($rtn);
}

/**
 * RIJNDAEL_256 encrypter
 *
 * @requires mcrypt package
 *
 * @param string $string_to_encrypt
 * @param mixed  $key
 *
 * @returns string base64 encoded
 */
function encrypt($string_to_encrypt, $key)
{
    $md5_key = md5($key);
    $iv      = md5($md5_key);
    $rtn     = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_encrypt, MCRYPT_MODE_CBC, $iv);
    $rtn     = base64_encode($rtn);
    
    return ($rtn);
}

/**
 * Random password generator
 *
 * @see http://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
 *
 * @param int $length
 *
 * @return string
 */
function randomPassword($length = 12)
{
    $alphabet    = "abcdefghijkmnpqrstuwxyz23456789";
    $pass        = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for( $i = 0; $i < $length; $i++ )
    {
        $n      = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    
    return implode($pass); //turn the array into a string
}

function make_unique_id($prefix)
{
    global $config;
    
    $server_id = $config->numeric_server_id;
    list($sec, $usec) = explode(".", round(microtime(true), 3));
    $return = $prefix . $server_id . $sec . sprintf("%03.0f", $usec);
    usleep(1000);
    return  $return;
}
