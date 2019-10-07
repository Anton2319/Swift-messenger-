<?php
$query = $_POST['query'];
$chat = $_POST['chat'];
function getdblenght() {
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    //request data from database
    $responce = mysqli_query($db, "SELECT COUNT(*) FROM `testconfa`");
    $result = mysqli_fetch_assoc($responce);
    return $result['COUNT(*)'];
}
function getfromdb($id) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    //request data from database
    $responce = mysqli_query($db, "SELECT * FROM `testconfa` WHERE id = ".$id."");
    $result = mysqli_fetch_assoc($responce);
    return $result;
}
$query = explode(" ", $query);
if($query['0']=="getdblenght") {
    echo getdblenght();
}
elseif($query['0']=="getfromdb") {
    echo getfromdb($query['1']);
}
?>