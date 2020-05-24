<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/maplayers.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
//require_once("models/user.php");

/* page options */
$params = array();
$params['sortlistby'] = (isset($_CLEAN['sortlistby']) ? $_CLEAN['sortlistby'] : 'disabled_layer_order');
$params['filterlistby'] = (isset($_CLEAN['filterlistby']) ? $_CLEAN['filterlistby'] : '');
$params['changedlayers'] = (isset($_CLEAN['changedlayers']) ? $_CLEAN['changedlayers'] : '');

$params['page'] = GetCleanInteger(isset($_CLEAN['page']) ? $_CLEAN['page'] : '1');
$params['scrollto'] = (isset($_CLEAN['scrollto']) ? $_CLEAN['scrollto'] : '');

global $siteconfig;
global $USER_SESSION;

/* get model for page content */
$maplayers = new MapLayers();
if ($USER_SESSION['siterole'] != 'admin') { //only admins see all layers (incl. those not shown)
    $maplayers->AddWhere('disabled','=',false);	
}

if ($params['filterlistby'] > '')
    $maplayers->AddWhere('filtercontent', '***', $params['filterlistby']);

$norecords = $maplayers->GetRecordsCount();
$startrecord = GetPageRecordOffset($siteconfig['display_gislayers_per_page'], $norecords, $params['page']);
$result = $maplayers->GetRecords($params['sortlistby'], $startrecord, $siteconfig['display_gislayers_per_page']);

/* put results into pager control */
$arrSorts = array();
$arrSorts['layer_order'] = getMLtext('layer_order');
$arrSorts['name'] = getMLText('layer_name');
$arrSorts['map_service'] = getMLText('map_service');
$arrSorts['allow_download'] = getMLText('download');
$arrSorts['allow_identify'] = getMLText('identify');
$arrSorts['allow_display_albertine'] = getMLText('display_albertine');
$arrSorts['allow_display_mountains'] = getMLText('display_mountains');
$arrSorts['allow_display_lakes'] = getMLText('display_lakes');
$arrSorts['layer_is_new'] = getMLText('new_layer');
$arrSorts['disabled'] = getMLText('is_disabled');
$arrSorts['disabled_layer_order'] = getMLtext('layer_order_default');

$arrListCols = array();
$arrListCols['geoserver_name'] = array();
$arrListCols['geoserver_name']['heading'] = getMLText('map_service');
$arrListCols['geoserver_name']['link'] = 'out.gislayer.php';
$arrListCols['geoserver_name']['linkparams'] = array('id' => 'id');
$arrListCols['layer_order'] = array();
$arrListCols['layer_order']['heading'] = getMLText('layer_order');
$arrListCols['displayname'] = array();
$arrListCols['displayname']['heading'] = getMLText('layer_name'); 
$arrListCols['projection'] = array();
$arrListCols['projection']['heading'] = getMLText('projection');
if ($USER_SESSION['siterole'] == 'admin') {
    $arrListCols['allow_download'] = array();
    $arrListCols['allow_download']['heading'] = getMLText('download') . "?";
    $arrListCols['allow_identify'] = array();
    $arrListCols['allow_identify']['heading'] = getMLText('identify') . "?";    
    $arrListCols['allow_display_albertine'] = array();
    $arrListCols['allow_display_albertine']['heading'] = getMLText('display_albertine') . "?";
    $arrListCols['allow_display_mountains'] = array();
    $arrListCols['allow_display_mountains']['heading'] = getMLText('display_mountains') . "?";
    $arrListCols['allow_display_lakes'] = array();
    $arrListCols['allow_display_lakes']['heading'] = getMLText('display_lakes') . "?";
    $arrListCols['layer_is_new'] = array();
    $arrListCols['layer_is_new']['heading'] = getMLText('new_layer') . "?";
    $arrListCols['disabled'] = array();
    $arrListCols['disabled']['heading'] = getMLText('is_disabled') . "?";
}

$pager = new Pager($norecords, $siteconfig['display_gislayers_per_page'], $params['page']);
$pageform = '';
$pageopts = '';

$pager->Setentries($result);
$pager->SetEntriesDisplay($arrListCols);
$pager->SetEntryTranslate('displayname'); //display translated value of lookup held in displayname
$pager->SetEntryTranslate('layer_is_new');
$pager->SetEntryBoolean('allow_download');
$pager->SetEntryBoolean('allow_identify');
$pager->SetEntryBoolean('allow_display_albertine');
$pager->SetEntryBoolean('allow_display_mountains');
$pager->SetEntryBoolean('allow_display_lakes');
$pager->SetEntryBoolean('disabled');
$pager->SetRowClassCondition('layer_is_new','!=','no', 'new_layer'); //only for admin
$pager->SetRowClassCondition('disabled', '==', 't', 'disabled_layer');
$setcriteria = array();

$pageform = $pager->ShowControlForm(url_for('out.listgislayers.php'), '', $params['page'], '', 'listanchor', $setcriteria);
$pageopts = $pager->ShowPageOptions($params['filterlistby'], $arrSorts, $params['sortlistby']);

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('map_layers')); 
$tpl->set('page_specific_head_content', 
    "<link rel='stylesheet' type='text/css' media='screen' href='css/listgislayers.css?random=" . filemtime($_SERVER["DOCUMENT_ROOT"].'/css/listgislayers.css') . "' />
	<script type='text/javascript' src='js/pageload.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);


/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/listgislayers.tpl.php');
$bdy->set('params', $params);
$bdy->set('pager', $pager);
$bdy->set('pageform', $pageform);
$bdy->set('pageopts', $pageopts);
$bdy->set('user',$USER_SESSION);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>