function postAndAlert(destination, parameters){
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