<?php
//leave language set
//setcookie("arbmis_lang", "", time()-3600, '/');

//if(isset($PHPSESSID)) { 
    session_start();
    //session_unregister("USER_SESSION");
    session_destroy();
//}

//Forward to index page
header("Location: out.index.php");
?>