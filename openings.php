<?php

class OpeningsScanner {

    static private $binaryPath = "./getOpenings/get_openings";


    /**
     * Finds openings in a specified STL binary file
     *
     * @param $stlPath string   The path to the mesh file
     * @return string           JavaScript compliant array or error object if the process failed
     */
    static function getOpenings($stlPath) {
        try {
            $openings = self::scanForOpenings($stlPath);
            return json_encode(self::calculateLocationAndSize($openings));
        } catch (Exception $e){
            return json_encode(["error" => true, "msg" => $e->getMessage()]);
        }
    }

    /**
     * Scans a binary STL mesh for openings
     *
     * @param $stlPath string   The path to the mesh file
     * @return array            Returns an array of openings. Each opening is an array of vertexes
     *                          defining that opening: [[vertexes], ...]
     * @throws Exception        If execution of the binary wasn't successful
     */
    static private function scanForOpenings($stlPath){
        $command = self::$binaryPath . " " . $stlPath;
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

    /**
     * @param $openings array   Array of all openings [[x,y,z], ...]
     * @return array            Location and size of the vector [[x,y,z], length]
     */
    static private function calculateLocationAndSize($openings){
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
                $openingSize = abs(self::length($center, $vertex));
                if ($openingSize > $maxSize){
                    $maxSize = $openingSize;
                }
            }
            $openingLocations[] = [$center, $maxSize];
        }
        return $openingLocations;
    }

    /**
     * Calculates the length of a 3D vector
     *
     * @param $point1 array     Start of the vector: [x,y,z]
     * @param $point2 array     End of the vector: [x,y,z]
     * @return float            Size of the vector
     */
    static private function length($point1, $point2){
        $xd = $point2[0]-$point1[0];
        $yd = $point2[1]-$point1[1];
        $zd = $point2[2]-$point1[2];
        return sqrt($xd*$xd + $yd*$yd + $zd*$zd);
    }
}