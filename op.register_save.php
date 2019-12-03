<?php

//check POST data and user credentials, 
//if ok, save and redirect to login
//if not, redirect to previous page

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("models/singleuser.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("includes/library_synch.php");

global $siteconfig;
global $USER_SESSION;

$_CLEANPOST = Sanitize($_POST);

$username = (isset($_CLEANPOST['username'])? $_CLEANPOST['username'] : ''); 
$email = (isset($_CLEANPOST['email'])? $_CLEANPOST['email'] : ''); 
$password = (isset($_POST['password'])? $_POST['password'] : ''); //do not clean this as any chars could be part of password

$save_ok = 0;
if (preg_match('/[^A-Za-z0-9_$]/', $username)) {
    $save_msg = getMLtext('username_not_valid');
} elseif ($username == '' || $password == '' || $email == '') {
    $save_msg = getMLtext('invalid_form_data');
} else {
    $singleuser = new SingleUser();
    $save_ok = $singleuser->CreateUser($username, $email, $password, $save_msg);
    if ($save_ok) {
        $library_synch = Library_SynchUserToLibrary($username, $email, $password);
        if (!$library_synch) $save_msg .= " (" . getMLtext('user_save_library_error') . ")";
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