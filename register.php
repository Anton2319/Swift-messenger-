<?php
$images = ['bg', 'bg1','bg2', 'bg3', 'bg4'];
$rand = $images[array_rand($images)];
?>
<!DOCTYPE html>
<html>
    <head>
        <link href="style.css" rel="stylesheet">
	    <meta charset = "utf-8">
	    <meta name="yandex-verification" content="8bfb5ff345ade99e" />
	    <title>Welcome! Swiftmessage</title>
	    <style>
            body{
                padding: 0px;
                margin: 0px;
                background: url(img/<?php echo $rand?>.jpg);
                background-position: center top;
                background-repeat: no-repeat;
            }
        </style>
    </head>
    <body>
        <div id = "navibar">
            <img src = "logo.svg">
           <h1 class='swiftmessagebg'>Swiftmessage Beta</h1> 
        </div>
        <div id = "loginblock">
        <form action = "reg.php" id = "loginform" method = "post">
            <input class = "textinput" type = "login" name = "login" placeholder = "Логин"><br>
            <input class = "textinput" type = "password" name = "password" placeholder = "Пароль"><br>
            <input class = "textinput" type = "email" name = "email" placeholder = "Ваш e-mail"><br>
            <input class = "textinput" type = "phone" name = "phone" placeholder = "Номер телефона (необязательно)"><br>
            <input class = "login_button" type = "submit" value = "Регистрация"><br>
        </form><br>
        <div class = "links_reg" style = "top: 60%;">
        <h4 class='have_account'><a class='have_account' href = "/">Уже есть аккаунт?</a></h4>
        </div>
        </div>
        <a href="http://statok.net/go/20263"><img src="//statok.net/imageOther/20263" alt="Statok.net" /></a>
    </body>
</html>
