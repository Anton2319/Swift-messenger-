<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$db = mysqli_connect("localhost", "site", "open2319", "users");
$login = mysqli_real_escape_string($db,$_POST['login']);
$password = mysqli_real_escape_string($db,$_POST['password']);
$password_hash = password_hash($password, PASSWORD_DEFAULT);
//request data from database
$responce = mysqli_query($db, "SELECT * FROM `auth` WHERE login = '".$_POST['login']."'");
$result = mysqli_fetch_assoc($responce);
if($result['password'] == $password) {
    session_start();
    $_SESSION['login'] = $login;
    header("Location: /messages");
}
else {
    if($result['password'] == $password_hash) {
    session_start();
    $_SESSION['login'] = $login;
    header("Location: /messages");
    }
    else {
    echo("<h2>Неверный логин или пароль!</h2><br>");
    }
}
?>