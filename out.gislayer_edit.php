<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singlemaplayer.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

$params = array();
$params['id'] = GetCleanInteger(isset($_CLEAN['id'])? $_CLEAN['id'] : '0');

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.listgislayers.php"); //user does not have permission to do this
    exit;
}

$layer = new SingleMapLayer($params['id']);
if ($params['id']) {
    $layerdata = $layer->GetAttributes();
    if (count($layerdata) == 0) { //invalid id
        header("Location: out.listgislayers.php");
        exit;
    }
} else {
    //new layer
}

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

$layernamekeys = getMLArrayStartingWith("maplayer_");

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('map_layer_edit'));
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/gislayer.css?random=" . filemtime($_SERVER["DOCUMENT_ROOT"].'/css/gislayer.css') . "' />
	<script type='text/javascript' src='js/pageload.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/gislayer_edit.tpl.php');
$bdy->set('layerdata',$layerdata);
$bdy->set('layernamekeys',$layernamekeys);
$bdy->set('session_msg', $session_msg);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>