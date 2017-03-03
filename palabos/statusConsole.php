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
    </style>
    <head>
        <title>WebVeins</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <script src="../httpCommunication.js"></script>
    </head>

    <body>
        <div id="console-info">
        <button onclick="killAll()">Kill process</button>
        </div>

        <div id="console" onscroll="consoleScrolled()"></div><br/>

        <script>
            var consoleDiv = document.getElementById("console");
            var lockScroll = true;
            updateConsole();
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