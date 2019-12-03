<?php
//check POST data and user credentials, 
//if ok, save and redirect to login
//if not, redirect to previous page

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singleuser.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("includes/library_synch.php");

global $siteconfig;
global $USER_SESSION;

$_CLEANPOST = Sanitize($_POST);

$id = GetCleanInteger(isset($_CLEANPOST['id'])? $_CLEANPOST['id'] : 0);
$key = (isset($_CLEANPOST['key'])? $_CLEANPOST['key'] : '');
$password = (isset($_POST['password'])? $_POST['password'] : ''); //do not clean this as any chars could be part of password

$save_ok = 0;
if ($id == 0 || $key == '' || $password == '') {
    $save_msg = getMLtext('invalid_form_data');
} else {
    $singleuser = new SingleUser();    
    $save_ok = $singleuser->PasswordResetAction($id, $key, $password);
    $save_msg = getMLtext('password_reset_error');
    if ($save_ok) {
        $save_msg = getMLtext('password_reset_success');
        $userdetails = $singleuser->GetUser($id);
        if (isset($userdetails['username'])) {
            Library_UpdateUserPassword($userdetails['username'], $password);
        }
    }
}
$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg, "state" => ($save_ok? "success" : "error"));
$session->SetSessionMsg($sess_data);
if ($save_ok) {
    header("Location: out.login.php");
} else {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
?>