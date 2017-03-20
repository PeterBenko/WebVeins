<?php
    define('RESULTS_FOLDER', "./results/");

	$files = glob(RESULTS_FOLDER. "*.gif");

	echo json_encode($files);