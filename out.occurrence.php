<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/singleoccurrence.php");
require_once("includes/inc.language.php");

global $siteconfig;
global $USER_SESSION;

$params = array();
$params['occ_id'] = GetCleanInteger(isset($_CLEAN['id'])? $_CLEAN['id'] : '0');
$params['region'] = (isset($_CLEAN['region'])? $_CLEAN['region'] : '');

if (!in_array($params['region'], array("albertine","mountains","lakes"))) $params['region'] = "";

$occ = new SingleOccurrence($params['occ_id']);
$occdata = $occ->GetAttributes();

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('occurrence_record'));
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/occurrence.css' />
	<script type='text/javascript' src='js/pageload.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('region',$params['region']);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/occurrence.tpl.php');
$bdy->set('occdata',$occdata);
$bdy->set('region',$params['region']);


/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>