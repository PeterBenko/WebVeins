function OpeningsManager(geometry, openingsTable){
    geometry.mergeVertices();
    var scanner = new OpeningsScanner(geometry);
    var openings = scanner.getOpeningsArray();
    var table = openingsTable;

    // Mark openings
    this.spheresParent = new THREE.Object3D();
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
        this.spheresParent.add( sphere );
    }

//            console.log(spheresParent);

    this.updateOpeningsTable = function(axisToSortBy){
        // Clear the table
        var tableViewModel = this.getOpenings( axisToSortBy );
        var minimum = getMinimumDistance(tableViewModel, 1);

        createTable(tableViewModel, axisToSortBy, minimum);
    };

    this.getOpenings = function( axisToSortBy ) {
        var openings = this.spheresParent.children;
        var valueToGet;

        switch ( axisToSortBy ){
            case "X":
                        //console.log("Sorting by X");
                valueToGet = getX; break;
            case "Y":
                        //console.log("Sorting by Y");
                valueToGet = getY; break;
            case "Z":
                        //console.log("Sorting by Z", axisToSortBy);
                valueToGet = getZ; break;
        }

        openings.sort( function( a, b ) {
            return valueToGet( a ) - valueToGet( b );
        });

        var tableViewModel = [];
        for (i = 0; i < openings.length; i++) {
            var opening = openings[i];
            var state = opening.userData.isOutlet? "Outlet" : "Inlet";
            var value = valueToGet(opening);
            tableViewModel.push( [state, value] );
        }

        return tableViewModel;
    };

    var getMinimumDistance = function( table, columnIndex ){

        var minimum;

        for (var i = 0; i < table.length-1; i++) {
            var thisValue = table[i][columnIndex];
            var nextValue = table[i+1][columnIndex];

            var distance = Math.abs( nextValue - thisValue );
            if (typeof minimum === 'undefined') {
                minimum = distance;
            }
            minimum = Math.min(distance, minimum);
        }

        return minimum;
    };

    var createTable = function( tableViewModel , axis, minimum ){

        while(table.hasChildNodes()){
            table.removeChild(table.firstChild);
        }

        var thead = document.createElement('thead');
        var tr = thead.insertRow( -1 );
        var td = tr.insertCell( -1 );
        td.innerHTML = "Opening status";
        td = tr.insertCell( -1 );
        td.innerHTML = axis + " - value of the opening";

        table.appendChild(thead);

        var tbody = document.createElement('tbody');

        for (i = 0; i < tableViewModel.length; i++) {
            var row = tableViewModel[i];
            tr = tbody.insertRow( -1 );
            td = tr.insertCell( -1 );
            td.innerHTML = row[0];

            td = tr.insertCell( -1 );
            td.appendChild(document.createTextNode('Cell'));
            td.innerHTML = row[1];
        }
        table.appendChild(tbody);

        var tfoot = document.createElement('thead');
        tr = tfoot.insertRow( -1 );
        td = tr.insertCell( -1 );
        td.setAttribute("colspan", "2");
        td.innerHTML = "Minimum distance between openings on " + axis + " axis: \<b\>" + minimum + "\</b\>";

        table.appendChild(tfoot);
    };

    var getX = function( a ){
        return a.position.x;
    };

    var getY = function( a ){
        return a.position.y;
    };

    var getZ = function( a ){
        return a.position.z;
    };
}