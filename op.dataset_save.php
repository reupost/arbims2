<?php

//check POST data and user credentials, 
//if ok, save and redirect to view layer (post 'layer saved')
//if not, redirect to edit layer (post message of error)

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singledataset.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$_CLEANPOST = Sanitize($_POST);

$data = array();
$data['datasetkey'] = (isset($_CLEANPOST['datasetkey'])? $_CLEANPOST['datasetkey'] : '');
$data['color'] = (isset($_CLEANPOST['color'])? $_CLEANPOST['color'] : '');
$data['region_albertine'] = (isset($_CLEANPOST['region_albertine'])? $_CLEANPOST['region_albertine'] : '');
$data['region_mountains'] = (isset($_CLEANPOST['region_mountains'])? $_CLEANPOST['region_mountains'] : '');
$data['region_lakes'] = (isset($_CLEANPOST['region_lakes'])? $_CLEANPOST['region_lakes'] : '');
$region = (isset($_CLEANPOST['region'])? $_CLEANPOST['region'] : '');

if (!in_array($region, array("albertine","mountains","lakes"))) $region = "";

$ds = new SingleDataset($data['datasetkey']);
$save_msg = "";
$save_ok = $ds->SetAttributes($data, $save_msg);

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg, "state" => ($save_ok? "success" : "error"));
$session->SetSessionMsg($sess_data);

if ($save_ok) {
    header("Location: out.listdatasets." . $region . ".php"); 
} else {
    header("Location: out.dataset_edit.php?datasetkey=" . $data['datasetkey'] . "&region=" . $region);
}
?>