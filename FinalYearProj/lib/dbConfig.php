<?php
//CONNECTS TO DB
$dbConnect = mysqli_connect("localhost", "root", "", "boardgamedb");

//VARIABLE THAT STORES DB CONNECTION STATUS
$dbStatus;
if($dbConnect){
    $dbStatus = "ONLINE";
}
else{
    $dbStatus = "OFFLINE";
}

//RUNS QUERY PASSED INTO FUNCTION
function dbQuery($queryString){
    global $dbConnect;
    $result = $dbConnect->query($queryString);
    return $result;
}
?>