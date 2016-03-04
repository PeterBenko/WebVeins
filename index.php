<html>
	<head>
		<title>WebVeins</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

    <body>
    <script src="./lib/three.js/three.min.js"></script>
    <script src="./lib/three.js/STLLoader.js"></script>
    <script src="./lib/three.js/OrbitControls.js"></script>
    <script src="./openings.js"></script>

    <script type="text/javascript"></script>

    <div id="content" style="float:left;">

    </div>

    <div id="controls" style="width: 300px; float:left">
        <label for="openingSize">Opening indicator size: 2</label>
        <br/>
        <input type="range" min="0.1" max="10" step="0.1" id="openingSize" value="2"
               oninput="showValue(this.id, 'Opening indicator size', this.value)"/>
        <br/>
    </div>

    <script type="text/javascript">
        function showValue(id, text, newValue)
        {
            getLabel(id).innerHTML = text + ": " + newValue;
        }
    </script>

    <script type="text/javascript">

        var container;

        var camera, controls, cameraTarget, scene, renderer, raycaster;

        var vein;

        var controlsWidth = 331;

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

            if ( vein &&
                 vein.userData.openings &&
                 vein.userData.openings.length > 0 )
            {
                var objectsToCheck = vein.userData.openings;

                var intersects = raycaster.intersectObjects( objectsToCheck );

                if ( intersects.length > 0 ) {
                    var intersection = intersects[ 0 ],
                        obj = intersection.object;
                    console.log("Clicked: ", obj.userData.id);
                    obj.userData.isOutlet = !obj.userData.isOutlet;
                    var color = obj.userData.isOutlet ? 0x0000ff : 0x00ff00;
                    obj.material.color.setHex(color);
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

            if ( vein &&
                 vein.userData.openings &&
                 vein.userData.openings.length > 0) {
                var openingIndicators = vein.userData.openings;
                for (var i = 0, len = openingIndicators.length; i < len; i++) {
                    var indicator = openingIndicators[i];
                    indicator.scale.set(indicatorScale, indicatorScale, indicatorScale);
                }
            }

            renderer.render( scene, camera );
        }

        function loadVein() {
            // Add vein mesh
            var loader = new THREE.STLLoader();
//            var material = new THREE.MeshPhongMaterial( { color: 0xFF0000, specular: 0x111111, shininess: 200 } );
            var material = new THREE.MeshLambertMaterial( { color: 0xFF0000, side: THREE.DoubleSide} );
            loader.load( './Resources/ourVein.stl', function ( geometry ) {
                vein = new THREE.Mesh( geometry, material );
//                vein.add( openingsSpheres );
                var newGeometry = new THREE.Geometry().fromBufferGeometry( geometry );
                var openingsObject = loadOpenings( newGeometry );
                vein.userData.openings = openingsObject.children;
                vein.add( openingsObject );

                vein.position.set( 0, -0.75, 0 );
                vein.rotation.set( - Math.PI / 2, 0, 0 );
                vein.scale.set( 1, 1, 1 );

                vein.castShadow = true;
                vein.receiveShadow = true;
                scene.add( vein );
            } );
        }

        function loadOpenings(geometry){
            geometry.mergeVertices();
            var scanner = new OpeningsScanner(geometry);
            var openings = scanner.getOpeningsArray();

            // Mark openings

            var openingsSpheres = new THREE.Object3D();

            for ( var i = 0, len = openings.length; i < len; i++ ) {
                // set up the sphere vars
                var segments = 16, rings = 16;

                // create the sphere's material
                var sphereMaterial =
                    new THREE.MeshLambertMaterial(
                        {
                            color: 0x0000FF,
                            transparent: true,
                            opacity: 0.7
                        });

                var opening = openings[i];

                var radius = opening[1];
                var center = opening[0];
                var sphere = new THREE.Mesh(

                    new THREE.SphereGeometry(
                        radius,
                        segments,
                        rings),

                    sphereMaterial );

                sphere.userData.id = i;
                sphere.userData.isOutlet = true;

                sphere.position.set( center.x, center.y, center.z );
                // add the sphere to the parent object
                openingsSpheres.add( sphere );
            }

//            console.log(openingsSpheres);
            return openingsSpheres;
        }

        function getLabel( needle ) {
            var labels = document.getElementsByTagName("label");
            for (var i = 0; i < labels.length; i++) {
                var label = labels[i];
                if(label.getAttribute("for") == needle) {
                    return label;
                }
            }
        }

    </script>
    </body>
</html>