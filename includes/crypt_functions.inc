<?php
/**
 * Cryptographic functions
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

/**
 * Decrypt a string, either using the internal 3le crypt or with mcrypt's RIJNDAEL_256
 *
 * @requires mcrypt package
 *
 * @param string $string_to_decrypt base64 encoded
 * @param string $key
 *
 * @return mixed
 */
function decrypt($string_to_decrypt, $key)
{
    if( substr($string_to_decrypt, 0, 4) == "3le:" )
    {
        $string_to_decrypt = substr($string_to_decrypt, 4);
        
        return three_layer_decrypt($string_to_decrypt, $key, md5($key), sha1($key));
    }
    
    $string_to_decrypt = base64_decode($string_to_decrypt);
    $md5_key           = md5($key);
    $iv                = md5($md5_key);
    $rtn               = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_decrypt, MCRYPT_MODE_CBC, $iv);
    $rtn               = rtrim($rtn, "\0\4");
    
    return ($rtn);
}

/**
 * Encrypt a given string using the internal 3 layer crypt
 *
 * @requires mcrypt package
 *
 * @param string $string_to_encrypt
 * @param mixed  $key
 *
 * @return string base64 encoded
 */
function encrypt($string_to_encrypt, $key)
{
    return "3le:" . three_layer_encrypt($string_to_encrypt, $key, md5($key), sha1($key));
    
    // $md5_key = md5($key);
    // $iv      = md5($md5_key);
    // $rtn     = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $md5_key, $string_to_encrypt, MCRYPT_MODE_CBC, $iv);
    // $rtn     = base64_encode($rtn);
    //
    // return ($rtn);
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
    usleep(10000);
    return  $return;
}

/**
 * Three-layer encryption
 *
 * @author LexZEUS
 * @see    http://lexzeus.tripod.com/
 *
 * @param string $text_plain
 * @param string $key1
 * @param string $key2
 * @param string $key3
 *
 * @return string
 */
function three_layer_encrypt($text_plain, $key1, $key2, $key3)
{
    return base64_encode(_3le_keyED(_3le_encrypt(_3le_keyED($text_plain, $key1), $key2), $key3));
}

/**
 * Three-layer decryption
 *
 * @author LexZEUS
 * @see    http://lexzeus.tripod.com/
 *
 * @param string $text_encrypted
 * @param string $key1
 * @param string $key2
 * @param string $key3
 *
 * @return string
 */
function three_layer_decrypt($text_encrypted, $key1, $key2, $key3)
{
    return _3le_keyED(_3le_decrypt(_3le_keyED(base64_decode($text_encrypted), $key3), $key2), $key1);
}

/**
 * Three-layer encryption internal encrypter
 *
 * @author LexZEUS
 * @see    http://lexzeus.tripod.com/
 *
 * @param string $txt
 * @param string $key
 *
 * @return string
 */
function _3le_encrypt($txt, $key)
{
    srand((double) microtime() * 1000000);
    $encrypt_key = md5(rand(0, 32000));
    $ctr         = 0;
    $tmp         = "";
    for( $i = 0; $i < strlen($txt); $i++ )
    {
        if( $ctr == strlen($encrypt_key) ) $ctr = 0;
        $tmp .= substr($encrypt_key, $ctr, 1) . (substr($txt, $i, 1) ^ substr($encrypt_key, $ctr, 1));
        $ctr++;
    }
    
    return _3le_keyED($tmp, $key);
}

/**
 * Three-layer encryption internal decrypter
 *
 * @author LexZEUS
 * @see    http://lexzeus.tripod.com/
 *
 * @param string $txt
 * @param string $key
 *
 * @return string
 */
function _3le_decrypt($txt, $key)
{
    $txt = _3le_keyED($txt, $key);
    $tmp = "";
    for( $i = 0; $i < strlen($txt); $i++ )
    {
        $md5 = substr($txt, $i, 1);
        $i++;
        $tmp .= (substr($txt, $i, 1) ^ $md5);
    }
    
    return $tmp;
}

/**
 * Three-layer encryption internal key transcoder
 *
 * @author LexZEUS
 * @see    http://lexzeus.tripod.com/
 *
 * @param string $txt
 * @param string $encrypt_key
 *
 * @return string
 */
function _3le_keyED($txt, $encrypt_key)
{
    $ctr = 0;
    $tmp = "";
    for( $i = 0; $i < strlen($txt); $i++ )
    {
        if( $ctr == strlen($encrypt_key) ) $ctr = 0;
        $tmp .= substr($txt, $i, 1) ^ substr($encrypt_key, $ctr, 1);
        $ctr++;
    }
    
    return $tmp;
}
