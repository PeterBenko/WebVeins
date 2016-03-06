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

$command = BINARY_PATH . " " . PARAMETERS;
echo $command;

$descriptorspec = array(
    1 => array("pipe", "w"),
    2 => array("pipe", "a")
);
$pipes = [];

$dir = dirname(__FILE__). "/" . RESULTS_FOLDER;
echo " in " . $dir + "<br/>";

$process = proc_open($command, $descriptorspec, $pipes, $dir);

/*
$descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("pipe", "a")
);
$pipes = [];
$process = proc_open('php', $descriptorspec, $pipes, null, null);

fwrite($pipes[0], '<?php print_r($_ENV); ?>');
fclose($pipes[0]);
*/

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

