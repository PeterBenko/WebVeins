<html xmlns="http://www.w3.org/1999/html">
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
        <script src="httpCommunication.js"></script>

        <div id="content" style="float:left;">

        </div>

        <div id="controls" style="width: 300px; float:left">
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
            <button onclick="postOpenings()">Submit</button>
            <button onclick="startCalculation()">Start calculation</button>
        </div>

        <script src="main.js"</script>
        
        <script type="text/javascript">

            function startCalculation(){
                location.href='./palabos/startCalculation.php';
            }
        </script>
    </body>
</html>