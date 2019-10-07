<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include("getdblenght.php");

$db = mysqli_connect("localhost", "site", "open2319", "users");
$login = $_POST['login'];
$password = mysqli_real_escape_string($db,$_POST['password']);
$password = password_hash($password, PASSWORD_DEFAULT);
$email = $_POST['email'];
$id = getdblenght("users","auth");
$id = $id + 1;
//request data from database
$responce = mysqli_query($db, "SELECT * FROM `auth` WHERE login = '".$login."'");
$result = mysqli_fetch_assoc($responce);
if($result['login'] == null) {
    $db = mysqli_connect("localhost", "site", "open2319", "users");
    $id = getdblenght("users","auth");
    $id = $id + 1;
    //request data from database
    $responce = mysqli_query($db, "INSERT INTO `auth` (`login`, `password`, `email`, `id`) VALUES ('".$login."','".$password."','".$email."',".$id.")");
    echo("INSERT INTO `auth` (`login`, `password`, `email`, `id`) VALUES ('".$login."','".$password."','".$email."',".$id.")");
    $result = mysqli_fetch_assoc($responce);
    if(!$responce) {
        $id++;
        $responce = mysqli_query($db, "INSERT INTO `auth` (`login`, `password`, `email`, `id`) VALUES ('".$login."','".$password."','".$email."',".$id.")");
        $result = mysqli_fetch_assoc($responce);
        if(!$responce) {
            echo("<h2>Ошибка регистрации</h2>");
            echo("<h3>Техподдержка: anton_2319@outlook.com, Telegram: @typicalprogrammer</h3>");
        }
    }
    // _____________
    // NEXT DATABASE
    // _____________
    $db = mysqli_connect("localhost", "site", "open2319", "user_contacts");
    $responce = mysqli_query($db, "CREATE TABLE ".$login." ( Name text, Status text, Type text, Uid text, id int(11) )");
    session_start();
    $_SESSION['login'] = $login;
    $_SESSION['password'] = $password;
    header("Location: messages");
}
else {
    echo("<h1>Имя пользователя уже занято, используйте другое!</h1>");
}
?>