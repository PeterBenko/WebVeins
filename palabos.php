<?php
define('templateFileLocation', "./palabos/templates/param.xml.template");
define('temporaryParametersLocation', "./palabos/tmp/");

//var_dump($_POST);
$openings = json_decode($_POST['openings']);
$axisName = $_POST['axis'];
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

$template = file_get_contents(templateFileLocation) or die("Unable read template!");
$filledTemplate = str_replace("**_OPENINGS_**", implode(" ", $openings), $template);
$filledTemplate = str_replace("**_AXIS_ID_**", $axisIndex, $filledTemplate);

if (!file_exists(temporaryParametersLocation)) {
    mkdir(temporaryParametersLocation, 0777, true);
}

$parametersFile = temporaryParametersLocation."param.xml";
$tempFile = fopen( $parametersFile, "w");
if ($tempFile){
    fwrite($tempFile, $filledTemplate);
    fclose($tempFile);
}

echo "Data successfully submitted!\n";
