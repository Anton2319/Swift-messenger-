<?php
$images = ['bg1','bg2', 'bg3', 'bg4'];
$rand = $images[array_rand($images)];
?>
<!DOCTYPE html>
<html>
    <head>
        <link href="style.css" rel="stylesheet">
        <link href="libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
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
        <nav class="navbar navbar-dark bg-dark">
            <a class="navbar-brand" href="#">
                <img src="logo.svg" width="30" height="30" class="d-inline-block align-top" alt="">
                <b>Swiftmessage</b>
            </a>
        </nav>
        <div class="container">
            <div class="row">
                <div class="col"></div>
                <div class="col-6 loginblock">
                    <h3 class="text-white"><b>Регистрация</b></h3>
                    <form action="reg.php" method="post">
                      <div class="form-group">
                        <input type="login" class="form-control" id="inputLogin" placeholder="Логин">
                      </div>
                      <div class="form-group">
                        <input type="password" class="form-control" id="inputPassword" placeholder="Пароль">
                      </div>
                      <div class="form-group">
                        <input type="email" class="form-control" id="inputEmail" placeholder="Ваш адрес электронной почты">
                      </div>
                      <button type="submit" class="btn btn-primary">Регистрация</button>
                    </form>
                </div>
                <div class="col"></div>
            </div>
        </div>
    </body>
</html>
