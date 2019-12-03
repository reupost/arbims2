<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tableoccurrence.php");
require_once("models/tabledatasets.php");
require_once("includes/inc.language.php");
//require_once("models/user.php");

global $siteconfig;
global $USER_SESSION;

/* page options */
$params = array();
$params['region'] = (isset($_CLEAN['region'])? $_CLEAN['region'] : '');

if (!in_array($params['region'], array("albertine","mountains","lakes"))) $params['region'] = "";

/* get model for page content */
$tbloccurrence = new TableOccurrence();
$tbldataset = new TableDatasets();

$searchfields = $tbloccurrence->GetSearchFields(); 

$valueFields = array();
foreach ($searchfields as $field) {
    $field_vals = $tbloccurrence->GetDistinctFieldValues($field, $params['region']);
    $valueFields[$field] = $field_vals;
}

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('occurrence_search'));
$tpl->set('page_specific_head_content', "<link rel='stylesheet' type='text/css' media='screen' href='css/searchoccurrence.css' />
	<script type='text/javascript' src='js/searchoccurrence.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('region',$params['region']);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/searchoccurrence.tpl.php');
$bdy->set('region', $params['region']);
$bdy->set('valueFields', $valueFields);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>