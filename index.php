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

    <div id="content"></div>
    <input type="range" min="0" max="10" step="0.1" id="openingSize" value="2"
           oninput="showValue(this.id, 'Opening indicator size', this.value)"/>
    <span id="openingSizeSpan">Opening indicator size: 2</span>

    <script type="text/javascript">
        function showValue(id, text, newValue)
        {
            document.getElementById(id+"Span").innerHTML = text + ": " + newValue;
        }
    </script>

    <script>

        var container;

        var camera, controls, cameraTarget, scene, renderer;

        var vein;

        var footerHeight = 100;
        init();
        animate();

        function init() {
            container = document.getElementById("content");

//            container = document.createElement( 'div' );
//            document.body.appendChild( container );

//            <canvas id="glcanvas" width="1280px" height="720px">

            camera = new THREE.PerspectiveCamera( 35, window.innerWidth / (window.innerHeight - footerHeight), 1, 15 );
            camera.position.set( 3, 2, 3 );

            cameraTarget = new THREE.Vector3( 0, 3, 0 );

            scene = new THREE.Scene();

            loadVein();

            // Lights
            scene.add( new THREE.HemisphereLight( 0xffffff, 0x0f0f1e ) );

            // renderer
            renderer = new THREE.WebGLRenderer( { antialias: true } );
            renderer.setClearColor( 0xfdfd96, 1 );
            renderer.setPixelRatio( window.devicePixelRatio );
//            renderer.setSize( window.innerWidth, window.innerHeight );
            renderer.setSize( window.innerWidth, window.innerHeight - footerHeight );

            controls = new THREE.OrbitControls( camera, renderer.domElement );
            //controls.addEventListener( 'change', render ); // add this only if there is no animation loop (requestAnimationFrame)
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            controls.enableZoom = true;

            container.appendChild( renderer.domElement );

            window.addEventListener( 'resize', onWindowResize, false );

        }

        function onWindowResize() {

            camera.aspect = window.innerWidth / (window.innerHeight - footerHeight);
            camera.updateProjectionMatrix();
            renderer.setSize( window.innerWidth, window.innerHeight - footerHeight );

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
//            console.log(" " + indicatorScale)

            if ( vein ) {
                var veinAccessories = vein.children;
                if ( veinAccessories.length > 0 ) {
                    var openingIndicators = veinAccessories[0].children;
                    if ( openingIndicators.length > 0 )
                        for (var i = 0, len = openingIndicators.length; i < len; i++) {
                            var indicator = openingIndicators[i]
                            indicator.scale.set(indicatorScale,indicatorScale,indicatorScale)
                        }
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
                var openings = loadOpenings( newGeometry );
                vein.add( openings );

                vein.position.set( 0, -0.75, 0 );
                vein.rotation.set( - Math.PI / 2, 0, 0 );
                vein.scale.set( 1, 1, 1 );

                vein.castShadow = true;
                vein.receiveShadow = true;
//                console.log( vein );
                scene.add( vein );
            } );
        }

        function loadOpenings(geometry){
            geometry.mergeVertices();
            var scanner = new OpeningsScanner(geometry);
            var openings = scanner.getOpeningsArray();

            // Mark openings

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

            var openingsSpheres = new THREE.Object3D();

            for ( var i = 0, len = openings.length; i < len; i++ ) {
                var opening = openings[i];

                var radius = opening[1];
                var center = opening[0];
                var sphere = new THREE.Mesh(

                    new THREE.SphereGeometry(
                        radius,
                        segments,
                        rings),

                    sphereMaterial );

                sphere.position.set( center.x, center.y, center.z );

                // add the sphere to the parent object
                openingsSpheres.add( sphere );
            }
            console.log(openingsSpheres);
            return openingsSpheres;
        }

    </script>
    </body>
</html>