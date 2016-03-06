<?php
define('RESULTS_FOLDER', "results");
// These two are relative to the results folder as the command will be executed there
define('BINARY_PATH', "../bin/aneurysm");
define('PARAMETERS', "../tmp/param.xml");

// if (!file_exists(PARAMETERS)) {
//     echo "Parameters file missing!";
//     return;
// }

$relativeResults = "./" . RESULTS_FOLDER;
if (!file_exists( $relativeResults)) {
    mkdir($relativeResults, 0777, true);
}
$files = glob("./" . RESULTS_FOLDER . "/*"); // get all file names
foreach($files as $file){ // iterate files
  if(is_file($file))
    unlink($file); // delete file
}

echo "Results folder cleared! <br/>";

$pipes = [];

//COMMENT LINES TO ENABLE REAL FUNCTIONALITY
$dir = './';
$command = 'php';
$descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("pipe", "a")
);
$process = proc_open('php', $descriptorspec, $pipes, $dir);

fwrite($pipes[0], '<?php phpinfo(); ?>');
fclose($pipes[0]);

// UNCOMMENT FOLLOWING LINES FOR REAL FUNCTIONALITY
// $dir = dirname(__FILE__). "/" . RESULTS_FOLDER;
// $command = BINARY_PATH . " " . PARAMETERS;

// $descriptorspec = array(
//     1 => array("pipe", "w"),
//     2 => array("pipe", "a")
// );
// $process = proc_open($command, $descriptorspec, $pipes, $dir);

echo "Command executed: " . $command  ." in " . $dir . "<br/>";

$status = -2;
if (is_resource($process)) {
    do {
        echo fgets($pipes[1]) . "<br/>"; //will wait for a end of line
        $status = proc_get_status($process);

    } while ($status['running']);

} else {
    echo "Cannot start process with command: " . $command . "\n";
}

fclose($pipes[1]);
fclose($pipes[2]);

$return_value = ($status["running"] ? proc_close($process) : $status["exitcode"] );

echo "Process exited with $return_value\n";