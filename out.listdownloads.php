<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tabledownloads.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

/* page options */
$params = array();
$params['sortlistby'] = (isset($_CLEAN['sortlistby'])? $_CLEAN['sortlistby'] : 'title');
$params['filterlistby'] = (isset($_CLEAN['filterlistby'])? $_CLEAN['filterlistby'] : '');
$params['page'] = GetCleanInteger(isset($_CLEAN['page'])? $_CLEAN['page'] : '1');

$params['scrollto'] = (isset($_CLEAN['scrollto'])? $_CLEAN['scrollto'] : '');

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

/* get model for page content */
$tbldownloads = new TableDownloads();

if ($params['filterlistby'] > '') $tbldownloads->AddWhere('filtercontent','***', $params['filterlistby']); //special case - no comparison operator

$norecords = $tbldownloads->GetRecordsCount();
$startrecord = GetPageRecordOffset($siteconfig['display_downloads_per_page'], $norecords, $params['page']);
$result = $tbldownloads->GetRecords($params['sortlistby'], $startrecord, $siteconfig['display_downloads_per_page']);

/* put results into pager control */
$arrSorts = array();
$arrSorts['download'] = getMLtext('download');
$arrSorts['download_date'] = getMLtext('date');
$arrSorts['username'] = getMLText('username');
$arrSorts['email'] = getMLText('email');
$arrSorts['numrecords'] = getMLText('occurrence_records');
$arrSorts['region'] = getMLText('region');

$arrListCols = array();
$arrListCols['id'] = array();
$arrListCols['id']['heading'] = getMLtext('download');
$arrListCols['download_date'] = array();
$arrListCols['download_date']['heading'] = getMLtext('date');
$arrListCols['username'] = array();
$arrListCols['username']['heading'] = getMLText('username');
$arrListCols['username']['link'] = 'out.user_edit.php';
$arrListCols['username']['linkparams'] = array('id' => 'user_id');
$arrListCols['email'] = array();
$arrListCols['email']['heading'] = getMLText('email');
$arrListCols['numrecords'] = array();
$arrListCols['numrecords']['heading'] = getMLText('occurrence_records');
$arrListCols['numrecords']['linkfromfield'] = 'url_full';


$pager = new Pager($norecords, $siteconfig['display_downloads_per_page'], $params['page']);
$pageform = '';
$pageopts = '';

$pager->Setentries($result);
$pager->SetEntriesDisplay($arrListCols);

$pageform = $pager->ShowControlForm(url_for('out.listdownloads.php'), '', $params['page'], '', 'listanchor');
$pageopts = $pager->ShowPageOptions($params['filterlistby'], $arrSorts, $params['sortlistby']);

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('download_list'));
$tpl->set('page_specific_head_content',
    "<link rel='stylesheet' type='text/css' media='screen' href='css/listdownloads.css' />
	<script type='text/javascript' src='js/pageload.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/listdownloads.tpl.php');
$bdy->set('params',$params);
$bdy->set('pager',$pager);
$bdy->set('pageform',$pageform);
$bdy->set('pageopts',$pageopts);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>