<?php

//check for bad GET params
if ($_GET['lang'] == 'en_GB') setcookie("arbmis_lang", 'en_GB', null, '/');
if ($_GET['lang'] == 'fr_FR') setcookie("arbmis_lang", 'fr_FR', null, '/');

header("Location: " . rawurldecode($_GET['referer'])); 
?>