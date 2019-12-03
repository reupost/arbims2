<?php
//check POST data and user credentials, 
//if ok, save 
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

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$_CLEANPOST = Sanitize($_POST);

$userdetails = array();
$userdetails['id'] = GetCleanInteger(isset($_CLEANPOST['id'])? $_CLEANPOST['id'] : 0);
$userdetails['language'] = (isset($_CLEANPOST['language'])? $_CLEANPOST['language'] : '');
$userdetails['email'] = (isset($_CLEANPOST['email'])? $_CLEANPOST['email'] : '');
$userdetails['activated'] = (isset($_CLEANPOST['activated'])? $_CLEANPOST['activated'] : '');
$userdetails['siterole'] = (isset($_CLEANPOST['siterole'])? $_CLEANPOST['siterole'] : '');

$delete = GetCleanInteger(isset($_CLEANPOST['delete'])? $_CLEANPOST['delete'] : 0);

$save_ok = 0;
$save_msg = "";
$singleuser = new SingleUser();
if ($delete) {
    $save_ok = $singleuser->DeleteUser($userdetails['id'], $save_msg);
    if ($save_ok) {
        $userdets = $singleuser->GetUser($userdetails['id']);
        if (isset($userdets['username'])) {
            Library_DeleteUser($userdets['username'], $password);
        }
    }
} else {
    $save_ok = $singleuser->SaveUser($userdetails, $save_msg);
}

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg, "state" => ($save_ok? "success" : "error"));
$session->SetSessionMsg($sess_data);

if ($save_ok) {
    header("Location: out.listusers.php");
} else {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
?>