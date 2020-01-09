<?php

//check POST data and user credentials, 
//if ok, save and redirect to view layer (post 'layer saved')
//if not, redirect to edit layer (post message of error)

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singlemaplayer.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.listgislayers.php"); //user does not have permission to do this
    exit; 
}

$_CLEANPOST = Sanitize($_POST);

$data = array();
$data['id'] = GetCleanInteger(isset($_CLEANPOST['id'])? $_CLEANPOST['id'] : '0');
$data['displayname'] = (isset($_CLEANPOST['displayname'])? $_CLEANPOST['displayname'] : '');
$data['layer_order'] = GetCleanInteger(isset($_CLEANPOST['layer_order'])? $_CLEANPOST['layer_order'] : '0');
$data['datafile_path'] = (isset($_CLEANPOST['datafile_path'])? $_CLEANPOST['datafile_path'] : '');
$data['allow_display_albertine'] = (isset($_CLEANPOST['allow_display_albertine'])? $_CLEANPOST['allow_display_albertine'] : '');
$data['allow_display_mountains'] = (isset($_CLEANPOST['allow_display_mountains'])? $_CLEANPOST['allow_display_mountains'] : '');
$data['allow_display_lakes'] = (isset($_CLEANPOST['allow_display_lakes'])? $_CLEANPOST['allow_display_lakes'] : '');
$data['allow_identify'] = (isset($_CLEANPOST['allow_identify'])? $_CLEANPOST['allow_identify'] : '');
$data['allow_download'] = (isset($_CLEANPOST['allow_download'])? $_CLEANPOST['allow_download'] : '');
$data['disabled'] = (isset($_CLEANPOST['disabled'])? $_CLEANPOST['disabled'] : '');

$layer = new SingleMapLayer($data['id']);
$save_msg = "";
$save_ok = $layer->SetAttributes($data, $save_msg);

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg);
$session->SetSessionMsg($sess_data);

if ($save_ok) {
    header("Location: out.listgislayers.php");
} else {
    header("Location: out.gislayer_edit.php?id=" . $data['id']);
}

?>