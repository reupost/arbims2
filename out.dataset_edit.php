<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("models/singledataset.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

$params = array();
$params['datasetid'] = (isset($_CLEAN['datasetid'])? $_CLEAN['datasetid'] : '');
$params['region'] = (isset($_CLEAN['region'])? $_CLEAN['region'] : '');

if (!in_array($params['region'], array("albertine","mountains","lakes"))) $params['region'] = "";

if ($USER_SESSION['siterole'] != 'admin') { //user does not have permission to do this
    if ($params['region'] > "") {
        header("Location: out.listdatasets." . $params['region'] . ".php"); 
    } else {
        header("Location: out.index.php");
    }
    exit;
}

$ds = new SingleDataset($params['datasetid']);
$dsdata = $ds->GetAttributes();
if (count($dsdata) == 0) { //invalid id
    if ($params['region'] > "") {
        header("Location: out.listdatasets." . $params['region'] . ".php"); 
    } else {
        header("Location: out.index.php");
    }
    exit;
}

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('dataset_edit'));
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/dataset.css' />
	<script type='text/javascript' src='js/jscolor/jscolor.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);
$tpl->set('region',$params['region']);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/dataset_edit.tpl.php');
$bdy->set('dsdata',$dsdata);
$bdy->set('region',$params['region']);
$bdy->set('session_msg', $session_msg); //in case of errors on save need to display msg

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>