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

        <script type="text/javascript"></script>

        <div id="content" style="float:left;">

        </div>

        <div id="controls" style="width: 300px; float:left">
            <label for="inlet-velocity"> Average inlet velocity (in m/s): </label>
            <input type="number" id="inlet-velocity" style="width: 8em" min="0" step="0.001" value="0.02"> <br/>

            <label for="openingSize">Opening indicator size: 2</label>
            <br/>
            <input type="range" min="0.1" max="10" step="0.1" id="openingSize" value="2"
                   oninput="showValue(this.id, 'Opening indicator scale factor', this.value)"/>
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



        <script type="text/javascript">
            function showValue(id, text, newValue)
            {
                getLabel(id).innerHTML = text + ": " + newValue;
            }
        </script>

        <script type="text/javascript">

            var stlToLoad = './res/ourVein.stl';

            var container;
            var camera, controls, cameraTarget, scene, renderer, raycaster;
            var vein, openingsManager;

            var controlsWidth = 332;

            init();
            animate();

            function init() {
                container = document.getElementById("content");
                container.addEventListener( 'mousedown', onDocumentMouseDown, false );

                var width = window.innerWidth - controlsWidth;
                var height = window.innerHeight;

                container.style.width = width + "px";

                camera = new THREE.PerspectiveCamera( 35, width / height, 1, 15 );
                camera.position.set( 3, 2, 3 );

                cameraTarget = new THREE.Vector3( 0, 3, 0 );

                scene = new THREE.Scene();

                loadVein();

                // Lights
                scene.add( new THREE.HemisphereLight( 0xffffff, 0x0f0f1e ) );

                // Raycaster
                raycaster = new THREE.Raycaster();

                // renderer
                renderer = new THREE.WebGLRenderer( { antialias: true } );
                renderer.setClearColor( 0xfdfd96, 1 );
                renderer.setPixelRatio( window.devicePixelRatio );
    //            renderer.setSize( window.innerWidth, window.innerHeight );
                renderer.setSize( width, height );

                controls = new THREE.OrbitControls( camera, renderer.domElement );
                //controls.addEventListener( 'change', render ); // add this only if there is no animation loop (requestAnimationFrame)
                controls.enableDamping = true;
                controls.dampingFactor = 0.25;
                controls.enableZoom = true;

                container.appendChild( renderer.domElement );

                window.addEventListener( 'resize', onWindowResize, false );
            }

            function onWindowResize() {

                var width = window.innerWidth - controlsWidth;
                var height = window.innerHeight;
                document.getElementById("content").style.width = width + "px";

                camera.aspect = width / height;
                camera.updateProjectionMatrix();
                renderer.setSize( width, height);
            }

            function onDocumentMouseDown( event ) {
                event.preventDefault();

                var mouse = new THREE.Vector2(
                    (  event.layerX  / renderer.domElement.width ) * 2 - 1,
                    - (  event.layerY  / renderer.domElement.height ) * 2 + 1);

                raycaster.setFromCamera( mouse, camera );

                if ( openingsManager ) {
                    var objectsToCheck = openingsManager.spheresParent.children;
                    var intersects = raycaster.intersectObjects( objectsToCheck );

                    if ( intersects.length > 0 ) {
                        var intersection = intersects[ 0 ],
                            obj = intersection.object;
    //                    console.log("Clicked: ", obj.userData.id);
                        obj.userData.isOutlet = !obj.userData.isOutlet;
                        var color = obj.userData.isOutlet ? 0x0000ff : 0x00ff00;
                        obj.material.color.setHex(color);

                        openingsManager.updateOpeningsTable();
                    }
                }
            }

            function animate() {
                requestAnimationFrame( animate );
                controls.update(); // required if controls.enableDamping = true, or if controls.autoRotate = true
                render();
            }

            function render() {

    //            var timer = Date.now() * 0.0005;
    //
    //            camera.position.x = Math.cos( timer ) * 3;
    //            camera.position.z = Math.sin( timer ) * 3;
    //
    //            camera.lookAt( cameraTarget );
    //
                var indicatorScale = parseFloat(document.getElementById("openingSize").value);

                if ( openingsManager ) {
                    var openings = openingsManager.spheresParent.children;
                    openings.forEach( function( indicator ){
                        indicator.scale.set(indicatorScale, indicatorScale, indicatorScale);
                    });
                }

                renderer.render( scene, camera );
            }

            function loadVein() {
                // Add vein mesh
                var loader = new THREE.STLLoader();
    //            var material = new THREE.MeshPhongMaterial( { color: 0xFF0000, specular: 0x111111, shininess: 200 } );
                var material = new THREE.MeshLambertMaterial( { color: 0xFF0000, side: THREE.DoubleSide} );
                loader.load( stlToLoad, function ( geometry ) {
                    var vein = new THREE.Mesh( geometry, material );
                    vein.add( loadOpenings( geometry ) );

                    vein.position.set( 0, -0.75, 0 );
                    vein.rotation.set( - Math.PI / 2, 0, 0 );
                    vein.scale.set( 1, 1, 1 );

                    vein.castShadow = true;
                    vein.receiveShadow = true;
                    scene.add( vein );

                    openingsManager.updateOpeningsTable();
                } );
            }

            function loadOpenings(geometry) {
                var table = document.getElementById("openings-table");
                var selector = document.getElementById("axis");
                var newGeometry = new THREE.Geometry().fromBufferGeometry( geometry );
                openingsManager = new OpeningsManager(newGeometry, table, selector);
                return openingsManager.spheresParent;
            }

            function axisChanged(){
                if ( openingsManager ){
                    openingsManager.updateOpeningsTable();
                }
            }

            function getLabel( needle ) {
                var labels = document.getElementsByTagName("label");
                for (var i = 0; i < labels.length; i++) {
                    var label = labels[i];
                    if (label.getAttribute("for") == needle) {
                        return label;
                    }
                }
            }

            function postOpenings() {
                if (!openingsManager) return;

                var url = "palabos/generateParameters.php";

                var axis = openingsManager.getAxisName();
                var velocity = document.getElementById("inlet-velocity").value;


                var tableViewModel = openingsManager.getTableViewModel(axis);
                var params = [];
                tableViewModel.forEach( function(row) {
                   params.push(row[0]);
                });

                var toSend = "openings=" + JSON.stringify(params);
                toSend += "&axis=" + axis;
                toSend += "&velocity=" + velocity;
                post(url, toSend);
            }

            function startCalculation(){
                location.href='./palabos/startCalculation.php';
            }

            function post(destination, parameters){
                var http = new XMLHttpRequest();

                http.open("POST", destination, true);
                http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                http.onreadystatechange = function() {//Call a function when the state changes.
                    if(http.readyState == 4 && http.status == 200) {
                        alert(http.responseText);
                    }
                };
                http.send(parameters);

            }
        </script>
    </body>
</html>