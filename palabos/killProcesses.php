<?php
define('PID_FOLDER', "./pids/");

$pids = scandir(PID_FOLDER);
$pidFiles = array_filter(scandir('./' . PID_FOLDER), function($item) {
    return !is_dir('./' . $item);
});
$allCount = count($pidFiles);
$successCount = 0;

foreach ($pidFiles as $pid) {
    $pidVal = floatval($pid);
    if (posix_getpgid($pidVal)){
        if (!posix_kill ( $pidVal ,  SIGTERM )){
            if(posix_kill($pidVal, SIGKILL)){
                $successCount++;
            }
        }
    } else {
        $allCount--;
        unlink(PID_FOLDER . $pid);
        echo "Deleted inactive: " . $pidVal . "\n";
    }
}

echo "Killed " . $successCount . " of " . $allCount;