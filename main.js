
var stlToLoad = './res/ourVein.stl';

var container;
var camera, controls, cameraTarget, scene, renderer, raycaster;
var vein, openingsManager, sliceManager;

var controlsWidth = 332;

THREE.Object3D.DefaultUp = new THREE.Vector3(0, 0, 1);

init();
animate();

function init() {
    container = document.getElementById("content");
    container.addEventListener( 'mousedown', onDocumentMouseDown, false );

    var width = window.innerWidth - controlsWidth - 1;
    var height = window.innerHeight;

    container.style.width = width + "px";

    camera = new THREE.PerspectiveCamera( 35, width / height, 1, 15 );
    camera.position.set( 3, 2, 4 );

    cameraTarget = new THREE.Vector3( 0, 3, 0 );

    scene = new THREE.Scene();

    loadVein();

    var helper = new THREE.AxisHelper( 0.1 );
    scene.add( helper );

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

            openingsManager.updateOpeningsTable(getAxisValue());
        }
    }
}

function indicatorScaleChanged(sender, text)
{
    var indicatorScale = sender.value;
    getLabel(sender.id).innerHTML = text + ": " + indicatorScale;

    if ( openingsManager ) {
        openingsManager.setOpeningsScale(indicatorScale);
    }
}

function animate() {
    requestAnimationFrame( animate );
    controls.update(); // required if controls.enableDamping = true, or if controls.autoRotate = true
    render();
}

function render() {
    renderer.render( scene, camera );
}

function loadVein() {
    // Add vein mesh
    var loader = new THREE.STLLoader();
//            var material = new THREE.MeshPhongMaterial( { color: 0xFF0000, specular: 0x111111, shininess: 200 } );
    var material = new THREE.MeshLambertMaterial( { color: 0xFF0000, side: THREE.DoubleSide} );
    loader.load( stlToLoad, function ( veinGeometry ) {
        var vein = new THREE.Mesh( veinGeometry, material );
        var box = new THREE.Box3().setFromObject( vein ); // Our model is above z=0, let's bring it to center 

        //allign box to positive octant
        vein.translateX(- box.min.x);
        vein.translateY(- box.min.y);
        vein.translateZ(- box.min.z);

        //move center to origin
        vein.translateX(-(box.max.x - box.min.x) / 2);
        vein.translateY(-(box.max.y - box.min.y) / 2);
        vein.translateZ(-(box.max.z - box.min.z) / 2);

        vein.castShadow = true;
        vein.receiveShadow = true;

        scene.add( loadSlice( vein ) );
        vein.add( loadOpenings( veinGeometry ) );
        scene.add( vein );

        openingsManager.updateOpeningsTable(getAxisValue());
    } );
}

function loadSlice(mesh){
    var sliceSelect = document.getElementById("slice-plane-selection");
    var sliceSlider = document.getElementById("slice-position");
    sliceManager = new SliceManager(mesh, sliceSelect, sliceSlider);
    return sliceManager.getBoundingBoxMesh();
}

function loadOpenings(geometry) {
    var table = document.getElementById("openings-table");
    var newGeometry = new THREE.Geometry().fromBufferGeometry( geometry );
    openingsManager = new OpeningsManager(newGeometry, table);
    return openingsManager.spheresParent;
}

function axisChanged(){
    if ( openingsManager ){
        openingsManager.updateOpeningsTable(getAxisValue());
    }
}

function getAxisValue(){
    var axisSelector = document.getElementById("axis");
    var value = axisSelector.options[axisSelector.selectedIndex].value;
    return value;
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

    var velocity = document.getElementById("inlet-velocity").value;

    var axis = getAxisValue();
    var tableViewModel = openingsManager.getOpenings(axis);
    var params = [];
    tableViewModel.forEach( function(row) {
       params.push(row[0]);
    });

    var sliceDirectionPlane = sliceManager.getSliceDirectionPlane();
    var slicePosition = sliceManager.getSlicePosition();

    var toSend = "openings=" + JSON.stringify(params);
    toSend += "&axis=" + axis;
    toSend += "&velocity=" + velocity;
    toSend += "&slice_direction=" + sliceDirectionPlane;
    toSend += "&slice_position=" + JSON.stringify({x: slicePosition.x, y: slicePosition.y, z: slicePosition.z});
    postAndAlert(url, toSend);
}