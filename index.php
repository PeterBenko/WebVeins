<html xmlns="http://www.w3.org/1999/html">
    <style>
        html,body { 
            height: 100%;
            width: 100%; 
            margin: 0px;
        }

        .box {
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            display: flex;
            -webkit-box-orient: vertical;
            -moz-box-orient: vertical;
            -ms-flex-direction: column;
            -webkit-flex-direction: column;
            flex-direction: column;
            padding: 5px;
        }

        .boxHeader {
            -ms-flex: 0 0 auto;
            -webkit-flex: 0 0 auto;
            flex: 0 0 auto;
        }

        .boxContent {
            margin-top:5px;
            -ms-flex: 1 0 auto;
            -webkit-flex: 1 0 auto;
            flex: 1 0 auto;
            -webkit-box-flex: 1.0;
        }

        .boxFooter {
            -ms-flex: 0 1 auto;
            -webkit-flex: 0 1 auto;
            flex: 0 1 auto;
        }

        #right-side{
            display: flex;
            margin: 0px;
            width: 300px; 
            height: 100%;
            flex-flow: column;
        }

        #openingSize {
            width: 100%;
        }

        #slice-position {
            width: 100%;
        }

    </style>
	<head>
		<title>WebVeins</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

    <body>
        <script src="./lib/three.js/three.min.js"></script>
        <script src="./lib/three.js/STLLoader.js"></script>
        <script src="./lib/three.js/OrbitControls.js"></script>
        <script src="openingsScanner.js"></script>
        <script src="openingsManager.js"></script>
        <script src="sliceManager.js"></script>
        <script src="httpCommunication.js"></script>

        <div id="content" style="float:left;">

        </div>

        <div id="right-side" class="box">
            <div id="controls" class="boxHeader">
                <label for="inlet-velocity"> Average inlet velocity (in m/s): </label>
                <input type="number" id="inlet-velocity" style="width: 8em" min="0" step="0.001" value="0.02"> <br/>

                <label for="openingSize">Opening indicator size: 2</label>
                <br/>
                <input type="range" min="0.1" max="10" step="0.1" id="openingSize" value="2"
                       oninput="indicatorScaleChanged(this, 'Opening indicator scale factor')"/>
                <br/>

                <label for="axis">Sorting axis:</label>
                <select id="axis" onchange="axisChanged()">
                    <option value="X">X</option>
                    <option value="Y">Y</option>
                    <option value="Z">Z</option>
                </select>
                <br/>

                <label for="openings-table"></label><br/>            
                <table id="openings-table" rules="all" style="width: 100%; border: 1px solid black; text-align:center;">
                </table>
                <br/>

                <label for="slice-plane-selection">Slice plane:</label>
                <select id="slice-plane-selection"">
                    <option value="XY">XY</option>
                    <option value="XZ">XZ</option>
                    <option value="YZ">YZ</option>
                </select>
                <br/>

                <label for="slice-position">Slice position:</label>
                <br/>
                <input  id="slice-position" type="range" min="0.0" max="1.0" step="0.01" value="0.5"/>
                <br/>

                <button onclick="postOpenings()">Submit</button>
                <button onclick="startCalculation()">Start calculation</button>
                <input type="checkbox" name="showModel" onchange="showModelChanged(this)" checked>Show model <br>
            </div>

            <div id="console-wrapper" class="boxContent box">
                <iframe src="./palabos/statusConsole.php" 
                        style="flex: 1 0 auto;" 
                        scrolling="no" 
                        frameBorder="0">
                    
                </iframe>
            </div>
        </div>
        
        <script src="main.js"></script>
    </body>
</html>