<?php

//check POST data and user credentials, 
//if ok, save and redirect to view layer (post 'layer saved')
//if not, redirect to edit layer (post message of error)

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("models/ipt.php");

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit; 
}

$ipt = new IPT();
$ipt->Unlock_IPT();
$msg = getMLtext('ipt_unlocked');

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $msg);
$session->SetSessionMsg($sess_data);

header("Location: out.ipt_synch.php");
?>