function SliceManager(veinMesh, sliceDirectionSelectElement, slicePositionElement){
	var sliceDirectionPlane;
	var positionValue = slicePositionElement.value;
	var slicePosition = new THREE.Vector3( positionValue, positionValue, positionValue);;
	var selectedTexture = 0;

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

	var createIndicator = function(geometry, textureIndex) {
		var indicatorGeometry = geometry.clone();
	  	var material = getMaterial(textureIndex);

    	indicatorGeometry.faceVertexUvs[0][0] = [ 
	    	new THREE.Vector2(1, 1), 
	    	new THREE.Vector2(0, 1), 
	    	new THREE.Vector2(1, 0)
	    	];
    	indicatorGeometry.faceVertexUvs[0][1] = [ 
	    	new THREE.Vector2(0, 1), 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(1, 0)
	    	];
    	indicatorGeometry.faceVertexUvs[0][2] = [ 
	    	new THREE.Vector2(1, 0), 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(1, 1)
	    	];
    	indicatorGeometry.faceVertexUvs[0][3] = [ 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(0, 1), 
	    	new THREE.Vector2(1, 1)
	    	];
    	indicatorGeometry.faceVertexUvs[0][4] = [ 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(0, 1), 
	    	new THREE.Vector2(1, 0)
	    	];
    	indicatorGeometry.faceVertexUvs[0][5] = [ 
	    	new THREE.Vector2(0, 1), 
	    	new THREE.Vector2(1, 1), 
	    	new THREE.Vector2(1, 0)
	    	];		
	    // Faces 6 and 7 are actually fine
    	indicatorGeometry.faceVertexUvs[0][8] = [ 
	    	new THREE.Vector2(0, 1), 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(1, 1)
	    	];
    	indicatorGeometry.faceVertexUvs[0][9] = [ 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(1, 0), 
	    	new THREE.Vector2(1, 1)
	    	];		
    	indicatorGeometry.faceVertexUvs[0][10] = [ 
	    	new THREE.Vector2(1, 1), 
	    	new THREE.Vector2(1, 0), 
	    	new THREE.Vector2(0, 1)
	    	];
    	indicatorGeometry.faceVertexUvs[0][11] = [ 
	    	new THREE.Vector2(1, 0), 
	    	new THREE.Vector2(0, 0), 
	    	new THREE.Vector2(0, 1)
	    	];
		
		indicatorGeometry.uvsNeedUpdate = true; 

		var indicatorMesh = new THREE.Mesh(indicatorGeometry, material);
		return indicatorMesh;
	}

	function getMaterial(index){
		if (index > 0) {
			var loader = new THREE.TextureLoader();
			var material1 = new THREE.MeshBasicMaterial( { map: loader.load('palabos/results/yz_0_' + index + '.gif') } );
	    	var material2 = new THREE.MeshBasicMaterial( { map: loader.load('palabos/results/yz_0_' + index + '.gif') } );
	    	var material3 = new THREE.MeshBasicMaterial( { map: loader.load('palabos/results/xz_0_' + index + '.gif') } );
	    	var material4 = new THREE.MeshBasicMaterial( { map: loader.load('palabos/results/xz_0_' + index + '.gif') } );
	    	var material5 = new THREE.MeshBasicMaterial( { map: loader.load('palabos/results/xy_0_' + index + '.gif') } );
	    	var material6 = new THREE.MeshBasicMaterial( { map: loader.load('palabos/results/xy_0_' + index + '.gif') } );
	  
		    var material = [material1, material2, material3, material4, material5, material6];

		} else {
			var dummyMaterial = new THREE.MeshBasicMaterial( {
			    color: 0xffffff
			} );
		    var material = [dummyMaterial, dummyMaterial, dummyMaterial, dummyMaterial, dummyMaterial, dummyMaterial];
		}

    	var meshFaceMaterial = new THREE.MeshFaceMaterial( material );
    	return meshFaceMaterial;
	}

	var boundingBoxGeometry = createBoundingBoxGeometry(veinMesh);
	var boundingBoxMesh = createBoundingBoxMesh(boundingBoxGeometry);
	var indicator = createIndicator(boundingBoxGeometry);
	boundingBoxMesh.add(indicator);

	var updateIndicator = function(){
		// Update texture
		indicator.material = getMaterial(selectedTexture);

		// Update scale and position
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

	this.sliceIndexChanged = function(newIndex){
	    selectedTexture = newIndex;
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