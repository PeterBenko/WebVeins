<?php
$file = $_POST["filePath"];
echo "Reading " . $file . "<br/>";
$template = file_get_contents($file) or die("Unable read " . $file);
echo $template;