<?php

//check POST data and user credentials, 
//if ok, save and redirect to my account page
//if not, redirect to previous page

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singleuser.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

$_CLEANPOST = Sanitize($_POST);
$lang = (isset($_CLEANPOST['language'])? $_CLEANPOST['language'] : '');
$password = (isset($_POST['password'])? $_POST['password'] : ''); //do not clean this as any chars could be part of password

$save_ok = 1;
$save_ok2 = 1;
$save_msg = '';
$save_msg2 = '';
if ($password == '' && $lang == '') {
    $save_msg = getMLtext('invalid_form_data');
    $save_ok = 0;
} else {
    $singleuser = new SingleUser();
    if ($password > '') {
        $save_ok = $singleuser->SetPassword($USER_SESSION['id'], $password);
        $save_msg = getMLtext('password_reset_error');
        if ($save_ok) {
            $save_msg = getMLtext('password_reset_success');
            $userdetails = $singleuser->GetUser($USER_SESSION['id']);
            if (isset($userdetails['username'])) {
                Library_UpdateUserPassword($userdetails['username'], $password);
            }
        }
    }
    if ($lang > '') {
        $save_ok2 = $singleuser->SetLanguage($USER_SESSION['id'], $lang);
        $save_msg2 = getMLtext('language_set_error');
        if ($save_ok2) {
            $save_msg2 = getMLtext('language_set_success');
            //set session language
            setcookie("arbmis_lang", $lang, null, '/');
        }
    }
}
if ($save_msg > '') {
    $save_msg_overall = $save_msg . ($save_msg2 > ''? "<br/>" . $save_msg2 : "");
} else {
    $save_msg_overall = $save_msg2;
}
$save_ok_overall = $save_ok && $save_ok2;

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg_overall, "state" => ($save_ok_overall? "success" : "error"));
$session->SetSessionMsg($sess_data);

if ($save_ok_overall) {
    header("Location: out.myaccount.php");
} else {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
?>