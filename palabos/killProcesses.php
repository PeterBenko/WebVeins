<?php
define('PID_FOLDER', "./pids/");

$pids = scandir(PID_FOLDER);
$pidFiles = array_filter(scandir('./' . PID_FOLDER), function($item) {
    return !is_dir('./' . $item);
});
$allCount = count($pidFiles);
$successCount = 0;

function pidIsActive($pid){
    return posix_kill( $pid, 0);
}

function purge($pid) {
    unlink(PID_FOLDER . $pid);
}

foreach ($pidFiles as $pid) 
{
    if (!pidIsActive($pid)) 
    {
        $allCount--;
        echo "Deleted inactive: " . $pid . "\n";
        purge($pid);
        continue;
    }

    $pidVal = floatval($pid);

    if (posix_kill($pidVal, 15))
    {
        echo "SIGTERMd $pidVal\n";
        $successCount++;
    } 
    else 
    {
        echo "Could not kill $pidVal\n";
    }

    if (pidIsActive($pidVal))
    {
        purge($pidVal);
    }
}

echo "Killed " . $successCount . " of " . $allCount;