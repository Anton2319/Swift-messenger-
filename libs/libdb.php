<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
function getdblenght($table) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    //request data from database
    if(empty($_GET['chat'])){
        $chatselected = "";
    }
    else{
        $responce = mysqli_query($db, "SELECT COUNT(*) FROM `".$table."`");
        $result = mysqli_fetch_assoc($responce);
        return $result['COUNT(*)'];
    }
}
function getcontactslenght($table) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_contacts");
    //request data from database
    $responce = mysqli_query($db, "SELECT COUNT(*) FROM `".$table."`");
    if($responce == NULL) {
        $result = '';
    }
    else {
        $result = mysqli_fetch_assoc($responce);
        return $result['COUNT(*)'];
    }
}
function getfromdb($id, $table) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    //request data from database
    $responce = mysqli_query($db, "SELECT * FROM `".$table."` WHERE id = ".$id."");
    $result = mysqli_fetch_assoc($responce);
    return $result;
}
function getcontact($table, $id) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_contacts");
    //request data from database
    $responce = mysqli_query($db, "SELECT * FROM `".$table."` WHERE id = ".$id);
    $result = mysqli_fetch_assoc($responce);
    return $result;
}
function getchatid($login ,$table) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_contacts");
    //request data from database
    $responce = mysqli_query($db, "SELECT * FROM `".$login."` WHERE Name = '".$table."'");
    if($responce == NULL) {
    $result = '';
    }
    else { 
        $result = mysqli_fetch_assoc($responce);
        return $result['Uid'];
    }
}
session_start();
$login = $_SESSION['login'];
if($login==null) {
    header("Location: /");
}
$db = mysqli_connect("localhost", "site", "open2319", "user_messages");
if(empty($_GET['chat'])) {
    $chatselected = "";
}
else {
    $chatselected = $_GET['chat'];
}
?>