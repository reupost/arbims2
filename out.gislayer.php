<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singlemaplayer.php");
require_once("includes/inc.language.php");

$params = array();
$params['id'] = GetCleanInteger(isset($_CLEAN['id'])? $_CLEAN['id'] : '0');

global $siteconfig;
global $USER_SESSION;

$layer = new SingleMapLayer($params['id']);
$layerdata = $layer->GetAttributes();
if (count($layerdata) == 0) { //invalid id
    header("Location: out.listgislayers.php");
    exit;
}

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('map_layer'));
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/gislayer.css' />
	<script type='text/javascript' src='js/pageload.js'></script>");
$tpl->set('site_user', $USER_SESSION);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/gislayer.tpl.php');
$bdy->set('layerdata',$layerdata);
$bdy->set('user',$USER_SESSION);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>