<?php
$images = ['bg1','bg2', 'bg3', 'bg4', 'bg5', 'bg6','bg7', 'bg8', 'bg9', 'bg10'];
$rand = $images[array_rand($images)];
?>
<!DOCTYPE html>
<html>
    <head>
        <style>
            body{
                padding: 0px;
                margin: 0px;
                background: url(img/<?php echo $rand?>.jpg);
                background-position: center top;
                background-repeat: no-repeat;
                
            }
        </style>
        <link href="libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
    	<title>Swiftmesage</title>
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
                    <h3 class="text-white"><b>Вход в Swiftmessage</b></h3>
                    <form action="login.php" method="post">
                      <div class="form-group">
                        <input type="login" name="login" class="form-control" id="inputLogin" placeholder="Логин">
                      </div>
                      <div class="form-group">
                        <input type="password" name="password" class="form-control" id="inputPassword" placeholder="Пароль">
                      </div>
                      <button type="submit" class="btn btn-primary">Войти</button>
                    </form>
                </div>
                <div class="col"></div>
            </div>
        </div>
    </body>
</html>
