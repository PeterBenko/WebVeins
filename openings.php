<?php

function getOpenings($stlPath) {
    $openings = scanForOpenings($stlPath);
    return calculateLocationAndSize($openings);
}

function scanForOpenings($stlPath){
    $binaryPath = "./getOpenings/get_openings";
    $command = $binaryPath . " " . $stlPath;
    exec($command, $output, $returnValue);
    if ($returnValue != 0) {
        throw new Exception("Command: " . $command . " exited with code: " . $returnValue);
    }

    $openings = [];
    foreach (explode("\n\n", $output) as $opening) {
        $thisOpening = [];
        foreach (explode("\n", $opening) as $vertex){
            $thisVertex = [];

            foreach (explode(" ", $vertex) as $coordinate) {
                if(strlen($coordinate) > 0){
                    $thisVertex[] = floatval($coordinate);
                }
            }

            if (count($thisVertex) > 0) {
                $thisOpening[] = $thisVertex;
            }
        }
        if (count($thisOpening) > 0){
            $openings[] = $thisOpening;
        }
    }

    return $openings;
}

function calculateLocationAndSize($openings){
    $openingLocations = [];
    foreach ($openings as $opening) {
        $openingX = 0;
        $openingY = 0;
        $openingZ = 0;

        foreach ($opening as $vertex) {
            $openingX += $vertex[0];
            $openingY += $vertex[1];
            $openingZ += $vertex[2];
        }

        $numberOfVertexes = count($opening);
        $openingX /= $numberOfVertexes;
        $openingY /= $numberOfVertexes;
        $openingZ /= $numberOfVertexes;

        $center = [$openingX, $openingY, $openingZ];
        $maxSize = 0;
        foreach ($opening as $vertex) {
            $openingSize = abs(distance($center, $vertex));
            if ($openingSize > $maxSize){
                $maxSize = $openingSize;
            }
        }
        $openingLocations[] = [$center, $maxSize];
    }
    return $openingLocations;
}

function distance($vector1, $vector2){
    $xd = $vector2[0]-$vector1[0];
	$yd = $vector2[1]-$vector1[1];
	$zd = $vector2[2]-$vector1[2];
    return sqrt($xd*$xd + $yd*$yd + $zd*$zd);
}