<?php
//Ru lang
//Documentation by Anton2319

//Last updated: 08.09.2019 12:30
//
//Eng lang
//Documentation by Anton2319

//Last updated: 08.09.2019 12:43
//
//Set display errors to true
//Any errors in this script will display in console and webpage
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//sesion_start();
function getdblenght($chatselected) {
    //Подключается к базе данных
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    //Запрашивает данные из user_messages, где chatselected таблица длину которой нужно сосчитать
    $responce = mysqli_query($db, "SELECT COUNT(*) FROM `".$chatselected."`");
    //Преобразует строку ответа в массив
    $result = mysqli_fetch_assoc($responce);
    //Возвращает длину таблицы в int
    return $result['COUNT(*)'];
}
function getchatid($login ,$name) {
    //Подключается к базе данных
    //Connects to database
    $db = mysqli_connect("localhost", "site", "open2319", "user_contacts");
    //Запрашивает данные из user_contacts где $login это имя таблицы, а $name имя контакта
    //Requests data from user_contacts where $login is a table name and $name is contact name
    $responce = mysqli_query($db, "SELECT * FROM `".$login."` WHERE Name = '".$name."'");
    //Преобразует строку ответа в массив
    //Converts responce string to array
    $result = mysqli_fetch_assoc($responce);
    //Возвращает uid чата (уникальный идентификатор)
    //Returns chat uid (unical identificator)
    return $result['Uid'];
}
$login = htmlspecialchars($_POST['login']);
$type = htmlspecialchars($_POST['Type']);
$chatselected = htmlspecialchars($_POST['chatselected']);
$msg = htmlspecialchars($_POST['message']);
$chatselected = getchatid($login, $chatselected);
$dblenght = getdblenght($chatselected);
$dblenght = $dblenght + 1;
if($type == "" || $type == null) {
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    //request data from database
    $responce = mysqli_query($db, "INSERT INTO `".$chatselected."`(`sender`, `text`, `datetime`, `id`) VALUES ('".$_POST['login']."','".$msg."',NOW(),".$dblenght.")");
}
else {
    $db = mysqli_connect("localhost", "site", "open2319", "user_messages");
    $responce = mysqli_query($db, "INSERT INTO `".$chatselected."`(`sender`, `text`, `datetime`, `id`) VALUES ('".$_POST['login']."','".$msg."',NOW(),".$dblenght.")");
    $chatselected = explode("-", $chatselected);
    $responce = mysqli_query($db, "INSERT INTO `".$chatselected['1']."-".$chatselected['0']."`(`sender`, `text`, `datetime`, `id`) VALUES ('".$_POST['login']."','".$msg."',NOW(),".$dblenght.")");
}
header("Location: /messages?chat=".$_POST['chatselected']);
?>