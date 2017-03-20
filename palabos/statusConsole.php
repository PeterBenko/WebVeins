<?php
    define('RESULTS_FOLDER', "results");
    define('OUTPUT', "out.txt");
?>
<html xmlns="http://www.w3.org/1999/html">
    <style>
        html,body { 
            height: 100%;
            width: 100%; 
            margin: 0px;
        }
        
        body {
            display: flex;
            flex-flow: column;
        } 
        
        #console{
            flex: 1 1 auto;
            border: 2px solid gray;
            padding: 5px;
            margin: 5px 0px 0px 0px;
            background-color: lightgray;
            overflow-y: scroll;
            white-space: pre-wrap;
        }

        #console-info {
            flex: 0 0 auto;
        }

        #result-slider {
            width: 100%;
        }
    </style>
    <head>
        <title>WebVeins</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <script src="../httpCommunication.js"></script>
    </head>

    <body>
        <div id="console-info">
            <button onclick="killAll()">Kill process</button>
            <input type="checkbox" name="showModel" onchange="showModel(this.checked)" checked>Show model <br>
            <label for="result-slider">Slice position:</label><br/>
            <input  id="result-slider" type="range" min="0.0" max="0.0" step="1" value="0" oninput="resultIndexChanged(this.value)" />
            <br/>   
        </div>

        <div id="console" onscroll="consoleScrolled()"></div><br/>

        <script>
            var consoleDiv = document.getElementById("console");
            var lockScroll = true;
            updateConsole();

            var resultSlider = document.getElementById("result-slider");
            updateSlice();

            function killAll(){
                var url = "./killProcesses.php";
                postAndAlert(url, null);
                updateConsole();
            }

            function consoleScrolled(){
                var delta = consoleDiv.scrollHeight - 
                            consoleDiv.offsetHeight - 
                            consoleDiv.scrollTop;

                if( delta > 10){
                    lockScroll = false;
                }
                else 
                {
                    lockScroll = true;
                }
            }

            function showModel(visible) {
                parent.vein.visible = visible; 
            }

            function resultIndexChanged(value) {
                parent.sliceManager.sliceIndexChanged(value);
            }

            function updateSlice(){

                var http = new XMLHttpRequest();

                http.open("POST", "./getImagesList.php", true);
                http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                http.onreadystatechange = function() {
                    if (http.readyState == 4 && http.status == 200) {
                        var arrayOfFiles = JSON.parse( http.responseText);
                        setResultCount(arrayOfFiles);
                    }
                };
                http.send();
                setTimeout(updateSlice, 500);
            }

            function setResultCount(files) {
                var count = files.length / 3 // 1 for each axis  
                resultSlider.max = count; 
            }

            function updateConsole(){

                var http = new XMLHttpRequest();

                http.open("POST", "./getContents.php", true);
                http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                http.onreadystatechange = function() {
                    if (http.readyState == 4 && http.status == 200) {
                        consoleDiv.innerHTML = http.responseText
                        if (lockScroll){
                            consoleDiv.scrollTop = consoleDiv.scrollHeight;
                        }
                    }
                };
                var parameters = "filePath=./" + "<?= RESULTS_FOLDER . "/" . OUTPUT  ?>";
                http.send(parameters);
                setTimeout(updateConsole, 500);
            }
        </script>
    </body>
</html>