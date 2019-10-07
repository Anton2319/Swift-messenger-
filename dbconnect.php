<?php
$returnto = $_POST['returnurl'];
//null for non return
$target = $_POST['target'];
//target database
$request = $_POST['request'];
//database request
//null for no-request
if($target == null || $target == "") {
    echo("ERROR! NO DATABASE CHOOSEN!");
    return 1;
}
$db = mysqli_connect("localhost", "site", "open2319", $target);
if(!$request == null) {
    //request data from database
    $responce = mysqli_query($request, $db);
    $result = mysqli_fetch_assoc($responce);
    //sending a result
    echo($result);
    echo("OK!");
}
else {
    echo("OK!");
}
if($returnto == null) {
    echo("OK!");
    return 0;
}
else {
    //return back if need
    header("Location: ".$returnto);
}
?>