function OpeningsScanner ( indexedGeometry ) {
    var geometry = indexedGeometry;

    this.getOpeningsArray = function() {
        var openings = findOpenings();
        return calculateLocationAndSize(openings);
    };

    var findOpenings = function() {
        // create connection lookup table
        var verticesCount = geometry.vertices.length;
        var lookup = new Array(verticesCount);
        for (var i = 0; i < verticesCount;){
            lookup[i++] = [];
        }

        var faces = geometry.faces;
        faces.forEach( function(face) {
            lookup[face.a].push(face.b);
            lookup[face.b].push(face.a);

            lookup[face.b].push(face.c);
            lookup[face.c].push(face.b);

            lookup[face.c].push(face.a);
            lookup[face.a].push(face.c);
        });

        //console.log("Lookup table: ", lookup);

        var edgeVertices = [];

        for ( i = 0; i < lookup.length; i++ ) {
            var wasRegistered = edgeVertices.some( function(opening) {
                return (opening.indexOf(i) > -1);
            });

            if (wasRegistered){
                continue;
            }

            var opening = findConnectedEdgeVertices(lookup, i, []);

            if (opening.length > 1) {
                //console.log("Opening around vertex ", i, ": ", opening);
                edgeVertices.push(opening);
            }
        }
        //console.log("Edge vertices: ", edgeVertices);
        return edgeVertices;
    };

    var findConnectedEdgeVertices = function(vertexConnections, thisVertex, thisOpening ){

        var connectingVertices = vertexConnections[thisVertex];

        if ( thisOpening.indexOf(thisVertex) > -1 ){ // Don't explore vertices that we already have in the opening group
            return thisOpening;
        } else {                                     // Add itself to the opening group
            thisOpening.push(thisVertex);
        }

        var prev = -1;
        var count = 0;

        connectingVertices.sort();
        for ( var i = 0; i < connectingVertices.length; i++ ) {
            var currentVertex = connectingVertices[i];

            if ( connectingVertices[i] !== prev ) { // We are at a new vertex index
                if (count == 1){
                    thisOpening = findConnectedEdgeVertices( vertexConnections, prev, thisOpening ); // Explore the edge neighbor recursively
                }

                if ( i == connectingVertices.length-1 ){ // There will be no next index to count, since this is a new index it is a unique as well
                    thisOpening = findConnectedEdgeVertices( vertexConnections, currentVertex, thisOpening );
                }

                count = 1;
            } else {
                count++
            }
            prev = currentVertex;
        }

        return thisOpening;
    };

    var calculateLocationAndSize = function( indexedOpenings ){
        var valuesOpenings = [];

        indexedOpenings.forEach( function( opening ) {
            var valuesOpening = [];

            // Find the center of the opening
            var center = new THREE.Vector3( 0, 0, 0 );
            opening.forEach( function(index) {
                center.add(geometry.vertices[index]);
            });
            center.divideScalar(opening.length);

            // Find the furthest vertex from the center point
            var maximumDistance = 0;
            opening.forEach( function(index) {
                var distance = center.distanceTo(geometry.vertices[index]);
                if (distance > maximumDistance){
                    maximumDistance = distance;
                }
            });

            valuesOpenings.push([center, maximumDistance]);
        });

        return valuesOpenings;
    };
}