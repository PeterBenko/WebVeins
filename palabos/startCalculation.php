<?php
define('RESULTS_FOLDER', "results");
// These two are relative to the results folder as the command will be executed there
define('BINARY_PATH', "../bin/aneurysm");
define('PARAMETERS', "../tmp/param.xml");
define('OUTPUT', "out.txt");
define('PID_FOLDER', "pids/");

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
$dir = dirname(__FILE__). "/" . RESULTS_FOLDER;
$output = $dir."/".OUTPUT;

/////
//COMMENT LINES TO ENABLE REAL FUNCTIONALITY
?><!--$command = 'php';

fopen($output, "a") or die("Unable to open output file");
$descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("file", $output, "a"),
    2 => array("file", $output, "a")
);
$process = proc_open('php', $descriptorspec, $pipes, $dir);

fwrite($pipes[0], '<?php phpinfo(); ?>');
fclose($pipes[0]);-->
<?php

/////
// UNCOMMENT FOLLOWING LINES FOR REAL FUNCTIONALITY
$command = "exec " . BINARY_PATH . " " . PARAMETERS; // Let it run in the background

$descriptorspec = array(
    1 => array("file", $output, "a"),
    2 => array("file", $output, "a")
);

session_write_close();
$process = proc_open($command, $descriptorspec, $pipes, $dir) or die("Could not start process with command: ". $command );
/////
// Remember the spawned processes
$status = proc_get_status($process);
$ppid = $status["pid"];

$pids = dirname(__FILE__) . "/" . PID_FOLDER;
if (!file_exists($pids)) {
    mkdir($pids, 0777, true);
}
touch( $pids . $ppid);
echo "Command executed: " . $status["command"]  ." in " . $dir . " with PID: " . $ppid . "<br/>";
//proc_close($process)
?>


<html xmlns="http://www.w3.org/1999/html">
    <style>
        #console{
            position: absolute;
            border: 3px solid gray;
            padding: 5px;
            margin: 5px 0px;
            background-color: lightgray;
            width: 500px;
            height: 400px;
            overflow-y: scroll;
            white-space: pre-wrap;
        }
    </style>
    <head>
        <title>WebVeins</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>

    <body>
        <script src="../httpCommunication.js"></script>

        <button onclick="killAll()">Kill process</button><br/>
        <div id="console"></div><br/>

        <script>
            updateConsole();

            function killAll(){
                var url = "./killProcesses.php";
                postAndAlert(url, null);
                updateConsole();
            }

            function updateConsole(){

                var consoleDiv = document.getElementById("console");

                var http = new XMLHttpRequest();

                http.open("POST", "./getContents.php", true);
                http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                http.onreadystatechange = function() {
                    if (http.readyState == 4 && http.status == 200) {
                        consoleDiv.innerHTML = http.responseText
                        consoleDiv.scrollTop = consoleDiv.scrollHeight;
                    }
                };
                var parameters = "filePath=./" + "<?= RESULTS_FOLDER . "/" . OUTPUT  ?>";
                http.send(parameters);
                setTimeout(updateConsole, 500);
            }
        </script>
    </body>
</html>