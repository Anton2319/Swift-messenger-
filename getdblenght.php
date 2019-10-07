<?php
function getdblenght($db, $table) {
    $db = mysqli_connect("localhost", "site", "open2319", $db);
    //request data from database
    $responce = mysqli_query($db, "SELECT COUNT(*) FROM `".$table."`");
    $result = mysqli_fetch_assoc($responce);
    return $result['COUNT(*)'];
}
?>