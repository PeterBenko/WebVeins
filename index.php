<?php include "./openings.php" ?>

<html>
	<head>
		<title>WebVeins</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

    <body>
    <script src="./lib/three.js/three.min.js"></script>
    <script src="./lib/three.js/STLLoader.js"></script>
    <script src="./lib/three.js/OrbitControls.js"></script>

    <script type="text/javascript">
    </script>

    <script>

        var container;

        var camera, controls, cameraTarget, scene, renderer;

        init();
        animate();

        function init() {

            container = document.createElement( 'div' );
            document.body.appendChild( container );

            camera = new THREE.PerspectiveCamera( 35, window.innerWidth / window.innerHeight, 1, 15 );
            camera.position.set( 3, 2, 3 );

            cameraTarget = new THREE.Vector3( 0, 3, 0 );

            scene = new THREE.Scene();

            var openingSpheres = loadOpenings();
            loadVein(openingSpheres);

            // Lights
            scene.add( new THREE.HemisphereLight( 0xffffff, 0x111122 ) );

            // renderer
            renderer = new THREE.WebGLRenderer( { antialias: true } );
            renderer.setClearColor( 0xfdfd96, 1 );
            renderer.setPixelRatio( window.devicePixelRatio );
            renderer.setSize( window.innerWidth, window.innerHeight );

            controls = new THREE.OrbitControls( camera, renderer.domElement );
            //controls.addEventListener( 'change', render ); // add this only if there is no animation loop (requestAnimationFrame)
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            controls.enableZoom = true;

            container.appendChild( renderer.domElement );

            window.addEventListener( 'resize', onWindowResize, false );

        }

        function onWindowResize() {

            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();

            renderer.setSize( window.innerWidth, window.innerHeight );

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
            renderer.render( scene, camera );

        }

        function loadVein(openingsSpheres) {
            // Add vein mesh
            var loader = new THREE.STLLoader();
//            var material = new THREE.MeshPhongMaterial( { color: 0xFF0000, specular: 0x111111, shininess: 200 } );
            var material = new THREE.MeshLambertMaterial( { color: 0xFF0000 } );
            loader.load( './getOpenings/ourVein.stl', function ( geometry ) {
                var vein = new THREE.Mesh( geometry, material );
                vein.add(openingsSpheres);

                vein.position.set( 0, 0, 0 );
                vein.rotation.set( - Math.PI / 2, 0, 0 );
                vein.scale.set( 1, 1, 1 );

                vein.castShadow = true;
                vein.receiveShadow = true;
                console.log(vein);
                scene.add( vein );
            } );
        }

        function loadOpenings(){
            // Mark openings

            // set up the sphere vars
            var segments = 16,
                rings = 16;

            // create the sphere's material
            var sphereMaterial =
                new THREE.MeshLambertMaterial(
                    {
                        color: 0x0000FF
                    });

            var openings = <?= json_encode(getOpenings("./getOpenings/ourVein.stl")) ?>;
            console.log(openings);
            var openingsSpheres = new THREE.Object3D();

            for (var i = 0, len = openings.length; i < len; i++) {
                var opening = openings[i];

                var radius = opening[1]*2;
                var center = opening[0];
                var sphere = new THREE.Mesh(

                    new THREE.SphereGeometry(
                        radius,
                        segments,
                        rings),

                    sphereMaterial);

                sphere.position.set(center[0], center[1], center[2] );

                // add the sphere to the parent object
                openingsSpheres.add(sphere);
            }

            return openingsSpheres;
        }

    </script>
    </body>
</html>