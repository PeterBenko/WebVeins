<?php
define('PID_FOLDER', "./pids/");

$pids = scandir(PID_FOLDER);
$pidFiles = array_filter(scandir('./' . PID_FOLDER), function($item) {
    return !is_dir('./' . $item);
});
$allCount = count($pidFiles);
$successCount = 0;

if ( count($pidFiles) != 1 ) {
	return true;
} 
else 
{
    return posix_kill( $pidFiles[0], 0);
}