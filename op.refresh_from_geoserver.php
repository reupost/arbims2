<?php

/*
 * get list of layer names from geoserver (in 'cite' workspace) 
 * add new ones to DB
 * delete those in DB not on geoserver
 */

require_once("includes/config.php");
require_once("models/maplayers.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.listgislayers.php"); //user does not have permission to do this
    exit;
}

$map = new MapLayers();

$new_layers = $map->UpdateLayersFromGeoserver();


if ($new_layers) {
    $msg = getMLtext('map_layers_changed');
} else {
    $msg = getMLtext('map_layers_unchanged');
}

$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $msg);
$session->SetSessionMsg($sess_data);

//Forward to GIS layers page
header("Location: out.listgislayers.php");
 
?>