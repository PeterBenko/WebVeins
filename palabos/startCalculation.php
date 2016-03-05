<?php
define('BINARY_PATH', "./bin/aneurysm");
define('PARAMETERS', "./tmp/param.xml");

if (!file_exists(PARAMETERS)) {
    echo "Parameters file missing";
    return;
}
$command = BINARY_PATH . " " . PARAMETERS;

$descriptorspec = array(
    1 => array("pipe", "w"),
    2 => array("pipe", "a")
);
$pipes = [];

$process = proc_open($command, $descriptorspec, $pipes, null, null);

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

