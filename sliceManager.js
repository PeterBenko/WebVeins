function SliceManager(veinMesh, sliceDirectionSelectElement, slicePositionElement){
	var sliceDirectionPlane;
	var positionValue = slicePositionElement.value;
	var slicePosition = new THREE.Vector3( positionValue, positionValue, positionValue);;

	var createBoundingBoxGeometry = function(veinMesh) {
	    var bbox = new THREE.Box3().setFromObject(veinMesh);
	    var bboxGeometry = new THREE.BoxGeometry(
	            bbox.max.x - bbox.min.x,
	            bbox.max.y - bbox.min.y,
	            bbox.max.z - bbox.min.z
	        );
	    //bboxGeometry.translate(
	    //    (bbox.max.x - bbox.min.x)/2 + bbox.min.x,
	    //    (bbox.max.y - bbox.min.y)/2 - bbox.min.y,
	    //    (bbox.max.z - bbox.min.z)/2 + bbox.min.z
	    //);	
	    return bboxGeometry;
	}

	var createBoundingBoxMesh = function(geometry) {
	    var bboxWireframeGeometry = new THREE.EdgesGeometry( boundingBoxGeometry ); // or WireframeGeometry( geometry )
	    var bboxMaterial = new THREE.LineBasicMaterial( {color: 0x0, transparent: true, opacity: 1.0} );

	    var boundingBoxMesh = new THREE.LineSegments( bboxWireframeGeometry, bboxMaterial );
	    return boundingBoxMesh;
	}

	var createIndicator = function(geometry) {
		var indicatorGeometry = geometry.clone();

		var material1 = new THREE.MeshBasicMaterial( { map: THREE.ImageUtils.loadTexture('res/yz_0_1201.jpg') } );
    	var material2 = new THREE.MeshBasicMaterial( { map: THREE.ImageUtils.loadTexture('res/yz_0_1201.jpg') } );
    	var material3 = new THREE.MeshBasicMaterial( { map: THREE.ImageUtils.loadTexture('res/yz_0_1201.jpg') } );
    	var material4 = new THREE.MeshBasicMaterial( { map: THREE.ImageUtils.loadTexture('res/yz_0_1201.jpg') } );
    	var material5 = new THREE.MeshBasicMaterial( { map: THREE.ImageUtils.loadTexture('res/yz_0_1201.jpg') } );
    	var material6 = new THREE.MeshBasicMaterial( { map: THREE.ImageUtils.loadTexture('res/yz_0_1201.jpg') } );
  
	    var materials = [material1, material2, material3, material4, material5, material6];
	  
	    var meshFaceMaterial = new THREE.MeshFaceMaterial( materials );

		var indicatorMesh = new THREE.Mesh(indicatorGeometry, meshFaceMaterial);
		return indicatorMesh;
	}

	var boundingBoxGeometry = createBoundingBoxGeometry(veinMesh);
	var boundingBoxMesh = createBoundingBoxMesh(boundingBoxGeometry);
	var indicator = createIndicator(boundingBoxGeometry);
	boundingBoxMesh.add(indicator);

	var updateIndicator = function(){
		switch(sliceDirectionPlane) {
    	case "XY":
        	indicator.scale.set(1, 1, 0.001);
        	var depth = boundingBoxGeometry.parameters.depth;
        	indicator.position.set(0, 0, slicePosition.z * depth - depth/2);
        	break;
    	case "XZ":
        	indicator.scale.set(1, 0.001, 1);
        	var height = boundingBoxGeometry.parameters.height;
        	indicator.position.set(0, slicePosition.y * height - height/2, 0);
        	break;
    	case "YZ":
        	indicator.scale.set(0.001, 1, 1);
        	var width = boundingBoxGeometry.parameters.width;
        	indicator.position.set(slicePosition.x * width - width/2, 0, 0);
        	break;
    	default:
        	console.error("Updating indicator for an unknown plane: " + value);
		}
	} 

	var planeChanged = function(){
		var value = sliceDirectionSelectElement.value;
		sliceDirectionPlane = value
		switch(sliceDirectionPlane) {
    	case "XY":
        	slicePositionElement.value = slicePosition.z;
        	break;
    	case "XZ":
        	slicePositionElement.value = slicePosition.y;
        	break;
    	case "YZ":
        	slicePositionElement.value = slicePosition.x;
        	break;
    	default:
        	console.error("Adjusting position of an unknown plane: " + value);
		}

		console.log(value);
		updateIndicator();
	}

	var positionChanged = function(){
		var value = slicePositionElement.value;
		switch(sliceDirectionPlane) {
    	case "XY":
        	slicePosition.z = value;
        	break;
    	case "XZ":
        	slicePosition.y = value;
        	break;
    	case "YZ":
        	slicePosition.x = value;
        	break;
    	default:
        	console.error("Adjusting position of an unknown plane: " + value);
		}

		console.log(slicePosition);
		updateIndicator();
	}

	sliceDirectionSelectElement.oninput = planeChanged;
	planeChanged();

	slicePositionElement.oninput = positionChanged;
	positionChanged();

	this.getBoundingBoxMesh = function(){
	    return boundingBoxMesh;
	}

	this.getSlicePosition = function() {
		return slicePosition;
	}

	this.getSliceDirectionPlane = function() {
		return sliceDirectionPlane;
	}
}