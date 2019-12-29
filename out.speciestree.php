<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tablespecies.php");
require_once("includes/inc.language.php");
//require_once("models/user.php");

/* page options */
$params = array();

global $siteconfig;
global $USER_SESSION;

/* get model for page content */
$tblspecies = new TableSpecies();

$pageform = '';
$pageopts = '';
$rows = array();

$txt = $tblspecies->GetAccordionBelow('', '*root*', true, true, false);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('species_explorer'));
$tpl->set('page_specific_head_content', "<link rel='stylesheet' type='text/css' media='screen' href='css/species.css' />
    <script src='js/datepicker/js/bootstrap-datepicker.js'></script>    
    <script type='text/javascript' src='js/jqtree/tree.jquery.js'></script>
	<script type='text/javascript' src='js/jquery.nestedAccordion.js'></script>
    <script type='text/javascript' src='js/speciesaccordion.js?v=1.0'></script>");
$tpl->set('site_user', $USER_SESSION);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/species.tpl.php');
$bdy->set('params', $params);
$bdy->set('txt', $txt);
$bdy->set('pageform', $pageform);
$bdy->set('pageopts', $pageopts);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>