<?php 
    echo "INSERT INTO `".$chatselected."-".$login."`(`sender`, `text`, `datetime`, `id`) VALUES ('".$_POST['login']."','".$msg."',NOW(),".$dblenght.")";
?> 

