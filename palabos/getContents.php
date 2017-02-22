<?php
$file = $_POST["filePath"];
echo "Reading " . $file . "<br/>";
$template = file_get_contents($file) or die("Unable to read " . $file);
echo $template;
