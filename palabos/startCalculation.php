
<?php
    define('RESULTS_FOLDER', "results");
    // These two are relative to the results folder as the command will be executed there
    define('BINARY_PATH', "../bin/aneurysm");
    define('PARAMETERS', "../tmp/param.xml");
    //
    define('OUTPUT', "out.txt");
    define('PID_FOLDER', "pids/");

    if (!file_exists( substr(PARAMETERS, 1) )) {
        echo "Parameters file missing!";
        return;
    }

    ob_start(); // Start output buffering
    include "./killProcesses.php";
    $list = ob_get_contents(); // Store buffer in variable
    ob_end_clean();

    //echo "<script> console.log(" . json_encode($list) . "); </script>";

    $relativeResults = "./" . RESULTS_FOLDER;
    if (!file_exists( $relativeResults)) {
        mkdir($relativeResults, 0777, true);
    }
    $files = glob("./" . RESULTS_FOLDER . "/*"); // get all file names
    foreach($files as $file){ // iterate files
        if(is_file($file))
            unlink($file); // delete file
    }

    //echo "Results folder cleared! <br/>";

    $pipes = [];
    $dir = dirname(__FILE__). "/" . RESULTS_FOLDER;
    $output = $dir."/".OUTPUT;

    /////
    //COMMENT LINES TO ENABLE REAL FUNCTIONALITY
    /*$command = 'php';

    fopen($output, "a") or die("Unable to open output file");
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("file", $output, "a"),
        2 => array("file", $output, "a")
    );
    $process = proc_open('php', $descriptorspec, $pipes, $dir);

    fwrite($pipes[0], '<?php phpinfo(); ?>');
    fclose($pipes[0]);*/
    

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
    $text = "Command executed: " . $status["command"]  ." in " . $dir . " with PID: " . $ppid;
    echo $text
    //proc_close($process)
?>