<?php
/**
 * Self running checker
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @see        http://php.net/manual/en/function.getmypid.php
 * @return bool
 */
function self_running_checker()
{
    # If lock file exists, check if stale.  If exists and is not stale, return TRUE
    # Else, create lock file and return FALSE.
    
    if( ! defined("LOCK_FILE") ) return false;
    
    if( file_exists(LOCK_FILE) )
    {
        # check if it's stale
        $lockingPID = trim(file_get_contents(LOCK_FILE));
        
        # Get all active PIDs.
        $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));
        
        # If PID is still active, return true
        if( in_array($lockingPID, $pids) ) return true;
        
        # Lock-file is stale, so kill it.  Then move on to re-creating it.
        # echo "Removing stale lock file.\n";
        unlink(LOCK_FILE);
    }
    
    file_put_contents(LOCK_FILE, getmypid() . "\n");
    
    return false;
}
