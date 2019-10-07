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
        <script src="https://code.jquery.com/jquery-latest.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700,900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Manjari&display=swap&subset=malayalam" rel="stylesheet">
        <link href="style.css" rel="stylesheet">
	    <meta name="yandex-verification" content="8bfb5ff345ade99e" />
	    <meta name="yandex-verification" content="5baa94f4aa8fad4d" />
	    <!-- Yandex.Metrika counter -->
        <script type="text/javascript" >
                (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
                (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
        
            ym(54999073, "init", {
                clickmap:true,
                trackLinks:true,
                accurateTrackBounce:true,
                webvisor:true
            });
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/54999073" style="display: none;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter -->
    	<title>Welcome! Swiftmessenger</title>
    </head>
    <body>
        <div id = "navibar">
            <img src = "logo.svg">
            <h1 class='swiftmessagebg'>Swiftmessage Beta</h1> 
        </div>
        <div id = "loginblock">
        <form action = "login.php" id = "loginform" method = "post">
            <input class = "textinput" type = "login" name = "login" placeholder = "Логин"><br>
            <input class = "textinput" type = "password" name = "password" placeholder = "Пароль"><br>
            <input class = "login_button" type = "submit" value = "Вход"><br>
        </form>
        <form action = "register.php" id = "loginform" method = "get">
            <input class = "register_button" type = "submit" value = "Регистрация"><br>
        </form>
            <div class = "links">
                <h4><a href = "forgot.php">Забыли пароль?</a></h4>
                <h4><a href = "mailto:anton_2319@outlook.com">Связь с техподдержкой</a></h4>
                <h4><a href = "https://teleg.one/typicalprogrammer">Telegram</a></h4>
            </div>
        </div>
    </body>
</html>
