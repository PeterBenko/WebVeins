<?php
define('TEMPLATE_FILE', "./templates/param.xml.template");
define('TEMPORARY_FOLDER', "./tmp/");

//var_dump($_POST);
$openings = json_decode($_POST['openings']);
$axisName = $_POST['axis'];
$velocity = $_POST['velocity'];
switch ($axisName) {
    case "X":
        $axisIndex = 0;
        break;
    case "Y":
        $axisIndex = 1;
        break;
    case "Z":
        $axisIndex = 2;
        break;
}

$template = file_get_contents(TEMPLATE_FILE) or die("Unable read template!");
$filledTemplate = str_replace("**_OPENINGS_**", implode(" ", $openings), $template);
$filledTemplate = str_replace("**_AXIS_ID_**", $axisIndex, $filledTemplate);
$filledTemplate = str_replace("**_INLET_VELOCITY_**", $velocity, $filledTemplate);

if (!file_exists(TEMPORARY_FOLDER)) {
    mkdir(TEMPORARY_FOLDER, 0777, true);
}

$parametersFile = TEMPORARY_FOLDER."param.xml";
$tempFile = fopen( $parametersFile, "w");
if ($tempFile){
    fwrite($tempFile, $filledTemplate);
    fclose($tempFile);
    echo "Data successfully submitted!\n";
    echo "Data used:\n";
    echo "Openings:";
    foreach ( $openings as $opening ) {
        echo " " . $opening;
    }
    echo "\n";
    echo "Axis: " . $axisName . " / " . $axisIndex . "\n";
    echo "Inlet velocity: " . $velocity . "\n";
    return;
} else {
    echo "Could not generate file!";
    return;
}