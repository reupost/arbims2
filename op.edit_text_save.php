<?php

//check POST data and user credentials, 
//if ok, save and redirect to correct text view page
//if not, redirect back to edit text page (post message of error)

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("models/usertext.php");

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$_CLEANPOST = Sanitize($_POST);

$data = array();
$data['key'] = (isset($_CLEANPOST['key'])? $_CLEANPOST['key'] : '');
$data['lang'] = (isset($_CLEANPOST['lang'])? $_CLEANPOST['lang'] : '');
$data['html'] = (isset($_POST['html'])? $_POST['html'] : ''); //use raw POST data

if (!in_array($data['key'], array("albertine","mountains","lakes","intro","partners"))) {
    header("Location: out.index.php"); //invalid key
    exit;
}

$save_msg = "";
$save_ok = SetUserText($data['key'], $data['lang'], $data['html'], $save_msg);

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg, "state" => ($save_ok? "success" : "error"));
$session->SetSessionMsg($sess_data);

if ($save_ok) {
    switch ($data['key']) {
        case "albertine"    :   header("Location: out.biodiversitydata.albertine.php"); break;
        case "mountains"    :   header("Location: out.biodiversitydata.mountains.php"); break;
        case "lakes"        :   header("Location: out.biodiversitydata.lakes.php"); break;
        case "intro"        :   header("Location: out.index.php"); break;
        case "partners"     :   header("Location: out.partners.php"); break;
        default             :   header("Location: out.index.php"); break;
    }
} else {
    header("Location: out.edit_text.php?key=" . $data['key']);
}
?>