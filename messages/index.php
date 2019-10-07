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
<!DOCTYPE html>
<html>
    <head>
        <script src="https://code.jquery.com/jquery-latest.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <script type="text/javascript">
        //HERE!!!
            $('#chat').css('top','99999px');
        </script>
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700,900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Manjari&display=swap&subset=malayalam" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
        <title>Swiftmessage Web Client</title>
        
    </head>
    <body>
        
        <div id = "leftblock">
        <div id = "search">
            <form id = "searchform" action = "search.php">
                <input class = "textinput" placeholder = "Поиск" type = "search" name = "search">
            </form>
        </div>
        <div id = "createChatBlock" class = "createChatBlock">
             <div class = "createChatBlock-content">
                
            </div>
        </div>
        
        <div id = "contacts">
                <?php            
                
                $lenght = getcontactslenght($login);
                $uid;
                if($lenght == 0) {
                    echo("<h2>Пока тут пусто!</h2>");
                }
                else {
                    $id = 0;
                    while($id < $lenght) {
                        $name = getcontact($login, $id)['Name'];
                        $uid = getchatid($login, $name);
                        $_GLOBALS['type'] = getcontact($login, $id)['Type'];
                        $type = getcontact($login, $id)['Type'];
                        if($type == null || $type == "") {
                            echo("
                            <a href = 'https://swiftmessage.tk/messages/?chat=".getcontact($login, $id)['Name']."'>
                            <div class = 'contact'>
                                <img src = '/media/".$uid."/avatar.jpg' width = 15%>
                                <h3>".getcontact($login, $id)['Name']."</h3><br>
                                    <p style = 'color: white;'>
                                    "
                                    .getcontact($login, $id)['Status'].
                                    "
                                    </p>
                                </div>
                                </a>
                            ");
                        }
                        else {
                            $username = explode("-", $uid);
                            echo("
                            <a href = 'https://swiftmessage.tk/messages/?chat=".getcontact($login, $id)['Name']."'>
                            <div class = 'contact'>
                                <img src = '/media/".$username['1']."/avatar.jpg' width = 15%>
                                <h3>".getcontact($login, $id)['Name']."</h3><br>
                                    <p style = 'color: white;'>
                                    "
                                    .getcontact($login, $id)['Status'].
                                    "
                                    </p>
                                </div>
                                </a>
                            ");
                        }
                    $id++;
                    }
                }
                ?>
        </div>
        </div>
        <div id = "mainblock">
            <div id = "chatinfo">
                <h2>
                <?php
                echo($chatselected);
                ?>
                </h2>
            </div>
            <div id = "chat">
            <?php

            $table = $chatselected;
            $table = getchatid($login, $table);
            $id = getdblenght($table);
            $startid = 1;
            if($table == "" || $table == null) {
                echo("<h1 style = 'color: white;'>Начните общение прямо сейчас!</h1>");
                echo("<h2 style = 'color: white;'>Выберите собеседника в меню \"Контакты\"!</h2>");
            }
            else {
            while($id >= $startid) {
                $attachment = explode("[:]", getfromdb($startid, $table)['text']);
                if($attachment['0'] == "img") {
                    if(getfromdb($startid, $table)['sender'] != $login) {
                            echo "<div style = 'color : white;'>".getfromdb($startid, $table)['sender']."</div>";
                            echo("
                            <div class = '.incoming' style = ' margin-top: 15px;
                            position: relative;
                            left: -0.3%;
                            top: 0px;
                            background-color: #FFF;
                            border-radius: 4px;
                            margin: 0.5em;
                            color: black;'><img width = 250px src = '/media/".$table."/avatar.jpg' />
                            </div>");
                            $startid++;
                        }
                        else {
                            echo "<div style = ' margin-top: 15px;
                            position: relative;
                            left: 79%;
                            top: 0px;
                            width: 20%;
                            border-radius: 4px;
                            margin: 0.5em;
                            color: white;'>".getfromdb($startid, $table)['sender']."</div>";
                            echo("
                            <div class = '.outgoing' style = ' margin-top: 15px;
                            position: relative;
                            left: 79%;
                            top: 0px;
                            background-color: #FFF;
                            border-radius: 4px;
                            margin: 0.5em;
                            color: black;'><img  width = 250px src = '/media/".$table."/avatar.jpg' /></div>
                            </div>
                            ");
                            $startid++;
                        }
                }
                elseif($attachment['0'] == "imgurl") {
                    if(getfromdb($startid, $table)['sender'] != $login) {
                            echo "<div style = 'color : white;'>".getfromdb($startid, $table)['sender']."</div>";
                            echo("
                            <div class = '.incoming' style = ' margin-top: 15px;
                            position: relative;
                            left: -0.3%;
                            top: 0px;
                            background-color: #FFF;
                            border-radius: 4px;
                            margin: 0.5em;
                            display: inline;
                            color: black;'><img width = 250px src = '".$attachment['1']."' />
                            </div>");
                            $startid++;
                        }
                        else {
                            echo "<div style = ' margin-top: 15px;
                            position: relative;
                            left: 79%;
                            top: 0px;
                            width: 20%;
                            border-radius: 4px;
                            margin: 0.5em;
                            color: white;'>".getfromdb($startid, $table)['sender']."</div>";
                            echo("
                            <div class = '.outgoing' style = ' margin-top: 15px;
                            position: relative;
                            left: 79%;
                            top: 0px;
                            background-color: #FFF;
                            border-radius: 4px;
                            margin: 0.5em;
                            display: inline;
                            color: black;'><img  width = 250px src = '".$attachment['1']."' /></div>
                            </div>
                            ");
                            $startid++;
                        }
                }
                else {
                    if(getfromdb($startid, $table)['sender'] != $login) {
                        echo "<div style = 'color : white;'>".getfromdb($startid, $table)['sender']."</div>";
                        echo("
                        <div class = '.incoming' style = ' margin-top: 15px;
                        position: relative;
                        left: -0.3%;
                        top: 0px;
                        background-color: #FFF;
                        display: inline-block;
                        border-radius: 4px;
                        margin: 0.5em;
                        color: black;'><p style = 'color: black;'>".getfromdb($startid, $table)['text']."</p></div>");
                        $startid++;
                    }
                    else {
                        echo "<div style = ' margin-top: 15px;
                        position: relative;
                        left: 79%;
                        top: 0px;
                        width: 20%;
                        border-radius: 4px;
                        margin: 0.5em;
                        color: white;'>".getfromdb($startid, $table)['sender']."</div>";
                        echo("
                        <div class = '.outgoing' style = ' margin-top: 15px;
                        position: relative;
                        left: 79%;
                        top: 0px;
                        width: 20%;
                        background-color: #FFF;
                        display: inline-block;
                        border-radius: 4px;
                        margin: 0.5em;
                        color: black;'><p style = 'color: black;'>".getfromdb($startid, $table)['text']."</p>
                        </div>
                        ");
                        $startid++;
                    }
                }
            }
            }
            ?>
            <div id = "message">
                <button class = "EmojiButton" style = "font-size: 24px;">🙂</button>
                <form id = "messageform" action = "sendmessage.php" method = "post">
                    <input autocomplete = "off" class = "textinput" type = "message" name = "message" placeholder = "Введите ваше сообщение" autofocus>
                    <input type = "hidden" name = "login" value = <?php echo($_SESSION['login']); ?>>
                    <input type = "hidden" name = "chatselected" value = <?php echo($chatselected); ?>>
                    <input style = "left: 7%;" class = "button" type = "submit" value = "Отправить">
                </form>
            </div>
            </div>
        </div>
        <a href="http://statok.net/go/20263"><img src="//statok.net/imageOther/20263" alt="Statok.net" /></a>
        	<script>
        		var modal = document.getElementById('EmojiBlock');
                var btn = document.getElementById("EmojiButton");
                var span = document.getElementsByClassName("EmojiClose")[0];
                btn.onclick = function() {
                    modal.style.display = "block";
                }
                span.onclick = function() {
                    modal.style.display = "none";
                }
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                }
	</script>
	<script type="text/javascript">
        var block = document.getElementById("chat");
        block.scrollTop = block.scrollHeight;
    </script>
    </body>
</html>