<?php
/**
 * CLI argument extractor
 *
 * @package    HNG2
 * @subpackage core
 * @author     Kaltar - kaltar@kaltar.org
 */

/**
 * @param $ARGUMENTS
 *
 * @return array
 */
function extract_cli_arguments($ARGUMENTS)
{
    $parametros = Array();
    $k = 0;
    if ( ! is_array($ARGUMENTS)) return $parametros;
    while (list ($key, $val) = each($ARGUMENTS))
    {
        if ($k > 0)
        {
            // recibimos en forma n=valor
            $ps = explode("=", $val);
            if ( ! isset($ps[1])) $ps[1] = 1;
            $parametros[$ps[0]] = $ps[1];
        } // end if
        $k ++;
    }
    return $parametros;
}

$arg = extract_cli_arguments($argv);
