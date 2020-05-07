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
require_once("data.load_shapefile.php");

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
$data['meta_source'] = (isset($_CLEANPOST['meta_source'])? $_CLEANPOST['meta_source'] : '');
$data['meta_sourcelink'] = (isset($_CLEANPOST['meta_sourcelink'])? $_CLEANPOST['meta_sourcelink'] : '');
$data['meta_citation'] = (isset($_CLEANPOST['meta_citation'])? $_CLEANPOST['meta_citation'] : '');
$data['meta_licence'] = (isset($_CLEANPOST['meta_licence'])? $_CLEANPOST['meta_licence'] : '');
$data['meta_sourcedate'] = (isset($_CLEANPOST['meta_sourcedate'])? $_CLEANPOST['meta_sourcedate'] : '');
$data['meta_description'] = (isset($_CLEANPOST['meta_description'])? $_CLEANPOST['meta_description'] : '');
$data['meta_classification_1'] = (isset($_CLEANPOST['meta_classification_1'])? $_CLEANPOST['meta_classification_1'] : '');
$data['meta_classification_2'] =  (isset($_CLEANPOST['meta_classification_2'])? $_CLEANPOST['meta_classification_2'] : '');

$data['has_new_gislayer'] = false;
$addToGeoserverResult = array(false,'');
if (isset($_FILES['layer-load-shp']) && $_FILES['layer-load-shp']['size'] > 0) {
    $addToGeoserverResult = AddLayerToGeoserver($_FILES['layer-load-shp'], $data['displayname']);
    if ($addToGeoserverResult[0] == true) {
        $data['in_geoserver'] = 't';
        $data['geoserver_name'] = $addToGeoserverResult[1];
        $data['layer_type'] = 'vector'; //TODO: sort out raster loading
        $data['has_new_gislayer'] = true; //use this flag to refresh database content for gislayer_feature
    } else {
        //leave existing geoserver details for layer - will fail to save if its a new layer and hence without geoserver details
    }
}

//TODO: add choice of styles when editing layer?
//TODO: test on raster layer - additional processing to populate raster_* table in postgres

$layer = new SingleMapLayer($data['id']);
$save_msg = "";
$save_state = "success";
$save_ok = $layer->SetAttributes($data, $save_msg);
if (!$addToGeoserverResult[0] && $addToGeoserverResult[1] != '') {
    $save_msg .= ' ' . $addToGeoserverResult[1];
    $save_state = "error";
}
$session = new SessionMsgHandler();
$sess_data = array("session_id" => $USER_SESSION['id'], "data_type" => "message", "data_value" => $save_msg, "state" => $save_state);
$session->SetSessionMsg($sess_data);

if ($save_ok) {
    header("Location: out.listgislayers.php");
} else {
    header("Location: out.gislayer_edit.php?id=" . $data['id']);
}

?>